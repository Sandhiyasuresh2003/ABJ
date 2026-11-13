<?php

namespace App\Services\Salary;

use App\Models\Employee;
use App\Models\SalaryAudit;
use App\Models\SalaryStatement;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalaryWorkflow
{
    public function __construct(private SalaryCalculator $calculator) {}

    public function audit(int $employeeId, string $action, ?array $before, array $after, ?string $reason = null): void
    {
        SalaryAudit::create(['employee_id' => $employeeId, 'actor_id' => auth()->id(), 'action' => $action, 'before' => $before, 'after' => $after, 'reason' => $reason]);
    }

    public function generate(Employee $employee, string $month): SalaryStatement
    {
        return DB::transaction(function () use ($employee, $month) {
            Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $statement = SalaryStatement::where('employee_id', $employee->id)->whereDate('salary_month', $month.'-01')->first() ?? new SalaryStatement(['employee_id' => $employee->id, 'salary_month' => $month.'-01']);
            if ($statement->status === 'Finalized') {
                return $statement;
            }
            $statement->snapshot = $this->calculator->calculate($employee, $month, (float) $statement->other_deductions);
            $statement->save();

            return $statement;
        }, 3);
    }

    public function change(SalaryStatement $statement, string $action, array $data): SalaryStatement
    {
        return DB::transaction(function () use ($statement, $action, $data) {
            Employee::whereKey($statement->employee_id)->lockForUpdate()->firstOrFail();
            $statement = SalaryStatement::lockForUpdate()->findOrFail($statement->id);
            $before = $statement->toArray();
            $month = $statement->salary_month->format('Y-m');
            if ($action === 'revise') {
                $this->ensure($statement->status === 'Finalized' && $statement->payment_status !== 'Paid', 'Only unpaid finalized statements can be revised. Reverse payment with a reason first.');
                $statement->status = 'Draft';
                $statement->revision++;
                $statement->finalized_at = null;
                $statement->finalized_by = null;
            } elseif ($action === 'adjust') {
                $this->ensure($statement->status === 'Draft', 'Revise the finalized statement before making adjustments.');
                $statement->other_deductions = $data['other_deductions'];
                $statement->adjustment_reason = $data['reason'];
            } elseif ($action === 'finalize') {
                $this->ensure($statement->status === 'Draft', 'This statement is already finalized.');
                $this->ensure(CarbonImmutable::parse($month.'-01', 'Asia/Kolkata')->endOfMonth()->isPast(), 'Finalize only after the salary month has ended.');
            } elseif ($action === 'payment') {
                $this->ensure($statement->status === 'Finalized', 'Finalize the salary before recording payment.');
                $statement->fill(collect($data)->only(['payment_status', 'payment_date', 'payment_reference'])->all());
                if ($data['payment_status'] === 'Pending') {
                    $statement->payment_date = null;
                    $statement->payment_reference = null;
                }
            }
            if ($action !== 'payment') {
                $statement->snapshot = $this->calculator->calculate($statement->employee, $month, (float) $statement->other_deductions);
            }
            if ($action === 'finalize') {
                $snapshot = $statement->snapshot;
                $this->ensure($snapshot['missing_days'] === 0 && count($snapshot['conflicts']) === 0, 'Resolve missing attendance and attendance/leave conflicts before finalizing.');
                $pending = $statement->employee->permissionRequests()->where('status', 'Pending')->whereDate('permission_date', '>=', $statement->salary_month->toDateString())->whereDate('permission_date', '<=', $statement->salary_month->copy()->endOfMonth()->toDateString())->exists()
                    || $statement->employee->leaveRequests()->where('status', 'Pending')->whereDate('start_date', '<=', $statement->salary_month->copy()->endOfMonth()->toDateString())->whereDate('end_date', '>=', $statement->salary_month->toDateString())->exists();
                $this->ensure(! $pending, 'Review pending permission and leave requests before finalizing.');
                $statement->status = 'Finalized';
                $statement->finalized_by = auth()->id();
                $statement->finalized_at = now();
            }
            $statement->save();
            $this->audit($statement->employee_id, $action, $before, $statement->toArray(), $data['reason'] ?? null);

            return $statement;
        }, 3);
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['salary' => $message]);
        }
    }
}
