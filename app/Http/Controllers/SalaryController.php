<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PermissionRequest;
use App\Models\SalaryAudit;
use App\Models\SalaryConfiguration;
use App\Models\SalaryStatement;
use App\Services\Salary\SalaryCalculator;
use App\Services\Salary\SalaryExport;
use App\Services\Salary\SalaryWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SalaryController extends Controller
{
    private function month(Request $request): string
    {
        return $request->validate(['month' => ['nullable', 'date_format:Y-m']])['month'] ?? now('Asia/Kolkata')->format('Y-m');
    }

    public function index(Request $request, SalaryCalculator $calculator)
    {
        $month = $this->month($request);
        $employees = Employee::orderBy('name')->get();
        $rows = [];
        $saved = SalaryStatement::whereDate('salary_month', $month.'-01')->get()->keyBy('employee_id');
        foreach ($employees as $employee) {
            $statement = $saved->get($employee->id);
            try {
                $snapshot = $statement?->status === 'Finalized' ? $statement->snapshot : $calculator->calculate($employee, $month, (float) $statement?->other_deductions);
                $rows[] = compact('employee', 'statement', 'snapshot') + ['error' => null];
            } catch (ValidationException $e) {
                $rows[] = compact('employee', 'statement') + ['snapshot' => null, 'error' => collect($e->errors())->flatten()->first()];
            }
        }
        $today = Attendance::whereDate('attendance_date', now('Asia/Kolkata')->toDateString())->get();
        $onLeave = LeaveRequest::where('status', 'Approved')->whereDate('start_date', '<=', now('Asia/Kolkata')->toDateString())->whereDate('end_date', '>=', now('Asia/Kolkata')->toDateString())->pluck('employee_id')->merge($today->whereIn('status', ['Casual Leave', 'Leave'])->pluck('employee_id'))->unique()->count();
        $metrics = [
            'Total Employees' => $employees->count(),
            'Employees Present Today' => $today->whereIn('status', ['Present', 'Late', 'Permission', 'Half Day'])->count(),
            'Employees Absent Today' => $today->where('status', 'Absent')->count(),
            'Employees on Leave Today' => $onLeave,
            'Employees on Week Off Today' => $today->where('status', 'Week Off')->count(),
            'Employees with Late Arrivals' => collect($rows)->filter(fn ($r) => ($r['snapshot']['late_arrivals'] ?? 0) > 0)->count(),
            'Pending Permission Requests' => PermissionRequest::where('status', 'Pending')->count(),
        ];

        return view('salary.index', compact('rows', 'employees', 'month', 'metrics'));
    }

    public function configure(Request $request, Employee $employee)
    {
        $month = $this->month($request);
        $configuration = SalaryConfiguration::where('employee_id', $employee->id)->whereDate('effective_month', '<=', $month.'-01')->latest('effective_month')->first() ?? new SalaryConfiguration;

        return view('salary.configuration', compact('employee', 'configuration', 'month'));
    }

    public function saveConfiguration(Request $request, Employee $employee, SalaryWorkflow $workflow)
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'], 'basic_salary' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'sunday_incentive' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'divisor_method' => ['required', Rule::in(['calendar', 'fixed'])],
            'fixed_divisor' => ['nullable', 'required_if:divisor_method,fixed', 'integer', 'min:1', 'max:366'],
            'working_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'permission_minutes' => ['required', 'integer', 'min:0', 'max:10000'],
            'late_charge_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'reporting_time' => ['required', 'date_format:H:i'],
            'cl_allowance' => ['required', 'integer', 'min:0', 'max:366'], 'cl_period' => ['required', Rule::in(['monthly', 'annual'])],
            'status' => ['required', Rule::in(['Active', 'Inactive'])], 'reason' => ['required', 'string', 'max:1000'],
        ]);
        if ($data['permission_minutes'] % $data['late_charge_minutes'] !== 0) {
            throw ValidationException::withMessages(['permission_minutes' => 'The allowance must contain a whole number of late-arrival blocks.']);
        }
        DB::transaction(function () use ($employee, $data, $workflow) {
            Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $profile = SalaryConfiguration::where('employee_id', $employee->id)->whereDate('effective_month', $data['month'].'-01')->first() ?? new SalaryConfiguration(['employee_id' => $employee->id, 'effective_month' => $data['month'].'-01']);
            $before = $profile->exists ? $profile->toArray() : null;
            $profile->fill(collect($data)->except(['month', 'reason'])->all())->save();
            $workflow->audit($employee->id, 'configuration', $before, $profile->toArray(), $data['reason']);
        }, 3);

        return redirect()->route('salary.index', ['month' => $data['month']])->with('success', 'Salary configuration saved. Finalized snapshots remain preserved.');
    }

    public function generate(Request $request, Employee $employee, SalaryWorkflow $workflow)
    {
        $statement = $workflow->generate($employee, $this->month($request));

        return redirect()->route('salary.show', $statement);
    }

    public function show(SalaryStatement $statement)
    {
        $audits = SalaryAudit::where('employee_id', $statement->employee_id)->latest('id')->get();
        $history = SalaryStatement::where('employee_id', $statement->employee_id)->orderByDesc('salary_month')->get();

        return view('salary.show', compact('statement', 'audits', 'history'));
    }

    public function change(Request $request, SalaryStatement $statement, string $action, SalaryWorkflow $workflow)
    {
        $rules = match ($action) {
            'adjust' => ['other_deductions' => ['required', 'numeric', 'min:0', 'max:999999999'], 'reason' => ['required', 'string', 'max:1000']],
            'revise' => ['reason' => ['required', 'string', 'max:1000']],
            'payment' => ['payment_status' => ['required', Rule::in(['Paid', 'Pending'])], 'payment_date' => ['nullable', 'required_if:payment_status,Paid', 'date_format:Y-m-d', 'before_or_equal:'.now('Asia/Kolkata')->toDateString()], 'payment_reference' => ['nullable', 'required_if:payment_status,Paid', 'string', 'max:255'], 'reason' => ['required', 'string', 'max:1000']],
            'finalize' => [],
        };
        $workflow->change($statement, $action, $request->validate($rules));

        return back()->with('success', 'Salary statement updated and audit recorded.');
    }

    public function export(SalaryStatement $statement, string $format, SalaryExport $export)
    {
        return $export->download($statement, $format);
    }
}
