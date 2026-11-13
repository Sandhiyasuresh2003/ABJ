<?php

namespace App\Services\Salary;

use App\Models\Employee;
use App\Models\SalaryConfiguration;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class SalaryCalculator
{
    public function calculate(Employee $employee, string $month, float $otherDeductions = 0): array
    {
        $start = CarbonImmutable::parse($month.'-01')->startOfMonth();
        $end = $start->endOfMonth()->startOfDay();
        $config = SalaryConfiguration::where('employee_id', $employee->id)->whereDate('effective_month', '<=', $start->toDateString())->latest('effective_month')->first();
        if (! $config || $config->status !== 'Active') {
            throw ValidationException::withMessages(['salary' => 'An active salary configuration is required for '.$employee->name.' in '.$month.'.']);
        }
        $joining = CarbonImmutable::parse($employee->joining_date)->startOfDay();
        if ($joining->greaterThan($end)) {
            throw ValidationException::withMessages(['salary' => 'Employee has not joined in this month.']);
        }
        $from = $joining->greaterThan($start) ? $joining : $start;
        $divisor = $config->divisor_method === 'calendar' ? $start->daysInMonth : $config->fixed_divisor;
        if (! $divisor || ! $config->working_minutes || ! $config->late_charge_minutes) {
            throw ValidationException::withMessages(['salary' => 'Salary divisor, working minutes and late charge must be positive.']);
        }
        // Currency is calculated in integer paise; day deductions round only at the category total.
        $basic = (int) round((float) $config->basic_salary * 100);
        $daily = $basic / $divisor;
        $eligibleDays = (int) $from->diffInDays($end) + 1;
        $earnedBasic = $from->equalTo($start) ? $basic : (int) round($basic * $eligibleDays / $start->daysInMonth);
        $records = $employee->attendanceRecords()->whereDate('attendance_date', '>=', $from->toDateString())->whereDate('attendance_date', '<=', $end->toDateString())->get()->keyBy(fn ($a) => $a->attendance_date->toDateString());
        $permissions = $employee->permissionRequests()->whereDate('permission_date', '>=', $from->toDateString())->whereDate('permission_date', '<=', $end->toDateString())->get();
        $clStart = $config->cl_period === 'annual' ? $start->startOfYear() : $start;
        $leaves = $employee->leaveRequests()->whereDate('start_date', '<=', $end->toDateString())->whereDate('end_date', '>=', $clStart->toDateString())->get();
        $clDates = [];
        $otherLeaves = [];
        foreach ($leaves as $leave) {
            if ($leave->status !== 'Approved') {
                continue;
            }
            $a = CarbonImmutable::parse($leave->start_date)->max($clStart)->max($joining);
            $b = CarbonImmutable::parse($leave->end_date)->min($end);
            for ($d = $a; $d->lessThanOrEqualTo($b); $d = $d->addDay()) {
                if ($leave->leave_type === 'Casual Leave') {
                    $clDates[$d->toDateString()] = true;
                } else {
                    $otherLeaves[$d->toDateString()] = $leave->leave_type;
                }
            }
        }
        // Legacy attendance-only CL uses the same allowance as approved leave requests.
        $legacyCl = $employee->attendanceRecords()->whereDate('attendance_date', '>=', $clStart->toDateString())->whereDate('attendance_date', '<=', $end->toDateString())->where('status', 'Casual Leave')->get();
        foreach ($legacyCl as $record) {
            if ($record->attendance_date->greaterThanOrEqualTo($joining)) {
                $clDates[$record->attendance_date->toDateString()] = true;
            }
        }
        ksort($clDates);
        $priorCl = count(array_filter(array_keys($clDates), fn ($d) => $d < $start->toDateString()));
        $clConsumed = $priorCl;
        $used = 0;
        $counts = array_fill_keys(['present_days', 'sunday_attendance', 'sundays', 'late_arrivals', 'cl_used', 'week_off_days', 'absent_days', 'missing_days'], 0);
        $units = array_fill_keys(['week_off', 'excess_cl', 'late', 'absence', 'half_day', 'unpaid_leave'], 0);
        $days = [];
        $conflicts = [];
        for ($date = $from; $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
            $key = $date->toDateString();
            $record = $records->get($key);
            $status = $record?->status;
            $approvedCl = isset($clDates[$key]);
            $otherLeave = $otherLeaves[$key] ?? null;
            $attended = in_array($status, ['Present', 'Late', 'Permission', 'Half Day'], true);
            if ($record && (($approvedCl && ! in_array($status, ['Casual Leave', 'Leave'], true)) || ($otherLeave && $status !== 'Leave'))) {
                $conflicts[] = $key.': attendance conflicts with approved leave';
            }
            if ($approvedCl && $otherLeave) {
                $conflicts[] = $key.': conflicting leave types';
            }
            if ($date->isSunday()) {
                $counts['sundays']++;
            }
            $approvedPermission = $permissions->contains(function ($p) use ($key, $record, $config) {
                return $p->permission_date->toDateString() === $key && $p->status === 'Approved'
                    && $record?->check_in && substr($p->start_time, 0, 5) <= substr($config->reporting_time, 0, 5)
                    && substr($p->end_time, 0, 5) >= substr($record->check_in, 0, 5);
            });
            $late = $attended && $record?->check_in && substr($record->check_in, 0, 5) > substr($config->reporting_time, 0, 5);
            if ($attended && ! $record->check_in) {
                $conflicts[] = $key.': check-in time required';
            }
            if ($status === 'Permission' && ! $approvedPermission) {
                $conflicts[] = $key.': permission attendance requires an approved request covering reporting time to check-in';
            }
            $deduction = null;
            $dayUnits = 0;
            // A date can have at most one salary deduction; approved leave takes precedence.
            if ($approvedCl) {
                $status = 'Casual Leave';
                $counts['cl_used']++;
                if (++$clConsumed > $config->cl_allowance) {
                    $deduction = 'excess_cl';
                    $dayUnits = 1;
                }
            } elseif ($otherLeave) {
                $status = $otherLeave;
                if ($otherLeave === 'Unpaid Leave') {
                    $deduction = 'unpaid_leave';
                    $dayUnits = 1;
                }
            } elseif ($status === 'Week Off') {
                $counts['week_off_days']++;
                $deduction = 'week_off';
                $dayUnits = 1;
            } elseif (in_array($status, ['Absent', 'Leave'], true)) {
                $counts['absent_days']++;
                $deduction = 'absence';
                $dayUnits = 1;
            } elseif ($attended) {
                $counts['present_days'] += $status === 'Half Day' ? 0.5 : 1;
                if ($date->isSunday() && $status !== 'Half Day') {
                    $counts['sunday_attendance']++;
                }
                if ($status === 'Half Day') {
                    $deduction = 'half_day';
                    $dayUnits = 0.5;
                }
                if ($late && ! $date->isSunday()) {
                    $counts['late_arrivals']++;
                    if (! $approvedPermission) {
                        if ($used + $config->late_charge_minutes <= $config->permission_minutes) {
                            $used += $config->late_charge_minutes;
                        } else {
                            // Full-day late penalty replaces a half-day deduction, never stacks with it.
                            $deduction = 'late';
                            $dayUnits = 1;
                        }
                    }
                }
            } elseif ($status !== 'Holiday') {
                $counts['missing_days']++;
                $status = 'Missing';
            }
            if ($deduction) {
                $units[$deduction] += $dayUnits;
            }
            $days[] = ['date' => $key, 'status' => $status, 'check_in' => $record?->check_in, 'check_out' => $record?->check_out, 'sunday' => $date->isSunday(), 'late' => (bool) $late && ! $date->isSunday(), 'approved_permission' => $approvedPermission, 'deduction' => $deduction, 'deduction_units' => $dayUnits];
        }
        $deductions = [];
        foreach ($units as $name => $value) {
            $deductions[$name] = (int) round($value * $daily);
        }
        $deductions['other'] = (int) round($otherDeductions * 100);
        $incentive = $from->equalTo($start) && $counts['sundays'] > 0 && $counts['sunday_attendance'] === $counts['sundays'] ? (int) round((float) $config->sunday_incentive * 100) : 0;
        $total = array_sum($deductions);

        return array_merge($counts, [
            'employee' => $employee->only(['id', 'employee_code', 'name', 'department', 'designation', 'joining_date']),
            'month' => $month, 'configuration' => $config->toArray(), 'divisor' => $divisor,
            'basic_salary' => $basic, 'earned_basic' => $earnedBasic, 'eligible_days' => $eligibleDays,
            'working_days' => $eligibleDays, 'daily_salary' => round($daily / 100, 6), 'per_minute_salary' => round($daily / $config->working_minutes / 100, 6),
            'sunday_incentive' => $incentive, 'permission_allowance' => (int) $config->permission_minutes,
            'permission_used' => $used, 'permission_balance' => $config->permission_minutes - $used,
            'cl_allowance' => (int) $config->cl_allowance, 'cl_prior_used' => $priorCl,
            'cl_balance' => max(0, $config->cl_allowance - $clConsumed),
            'deduction_units' => $units, 'deductions' => $deductions, 'total_deductions' => $total,
            'payable_salary' => max(0, $earnedBasic + $incentive - $total),
            'days' => $days, 'conflicts' => $conflicts,
            'permissions' => $permissions->toArray(), 'leaves' => $leaves->toArray(),
            'calculated_at' => now()->toIso8601String(), 'calculation_version' => 1,
        ]);
    }
}
