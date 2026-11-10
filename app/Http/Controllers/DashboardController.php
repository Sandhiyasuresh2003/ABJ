<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PermissionRequest;
use App\Models\PettyCashTransaction;
use App\Models\SalaryConfiguration;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = Carbon::today();
        $employees = Employee::where('employment_status', 'Active')->count();
        $present = Attendance::whereDate('attendance_date', $today)->whereIn('status', ['Present', 'Late'])->count();
        $late = Attendance::whereDate('attendance_date', $today)->where('status', 'Late')->count();
        $pendingLeaves = LeaveRequest::where('status', 'Pending')->count();
        $pendingPermissions = PermissionRequest::where('status', 'Pending')->count();

        $salaryMonth = Carbon::now('Asia/Kolkata')->startOfMonth()->toDateString();
        $latestSalaryMonths = SalaryConfiguration::query()
            ->selectRaw('employee_id, MAX(effective_month) as effective_month')
            ->whereDate('effective_month', '<=', $salaryMonth)
            ->groupBy('employee_id');
        $salaryTotals = SalaryConfiguration::query()
            ->joinSub($latestSalaryMonths, 'latest_salary_months', function ($join) {
                $join->on('salary_configurations.employee_id', '=', 'latest_salary_months.employee_id')
                    ->on('salary_configurations.effective_month', '=', 'latest_salary_months.effective_month');
            })
            ->join('employees', 'employees.id', '=', 'salary_configurations.employee_id')
            ->where('employees.employment_status', 'Active')
            ->where('salary_configurations.status', 'Active')
            ->selectRaw('COUNT(*) as configured_employees, COALESCE(SUM(salary_configurations.basic_salary), 0) as total_monthly_salary')
            ->first();
        $totalMonthlySalary = (float) $salaryTotals->total_monthly_salary;
        $salaryConfiguredEmployees = (int) $salaryTotals->configured_employees;

        $cashIn = (float) PettyCashTransaction::where('type', 'in')->sum('amount');
        $cashOut = (float) PettyCashTransaction::where('type', 'out')->sum('amount');
        $cashBalance = $cashIn - $cashOut;

        $recentAttendance = Attendance::with('employee')->whereDate('attendance_date', $today)
            ->latest('updated_at')->take(6)->get();
        $pendingLeaveItems = LeaveRequest::with('employee')->where('status', 'Pending')->latest()->take(4)->get();
        $pendingPermissionItems = PermissionRequest::with('employee')->where('status', 'Pending')->latest()->take(4)->get();
        $recentCash = PettyCashTransaction::latest('transaction_date')->latest('id')->take(5)->get();

        return view('dashboard.index', compact(
            'today', 'employees', 'present', 'late', 'pendingLeaves', 'pendingPermissions',
            'totalMonthlySalary', 'salaryConfiguredEmployees',
            'cashIn', 'cashOut', 'cashBalance', 'recentAttendance', 'pendingLeaveItems',
            'pendingPermissionItems', 'recentCash'
        ));
    }
}
