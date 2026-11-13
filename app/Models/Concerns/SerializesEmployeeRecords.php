<?php

namespace App\Models\Concerns;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PermissionRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

trait SerializesEmployeeRecords
{
    public function save(array $options = [])
    {
        return DB::transaction(function () use ($options) {
            Employee::whereIn('id', array_unique(array_filter([$this->employee_id, $this->getOriginal('employee_id')])))->orderBy('id')->lockForUpdate()->get();
            if ($this instanceof Attendance) {
                $duplicate = static::where('employee_id', $this->employee_id)->whereDate('attendance_date', $this->attendance_date)->when($this->exists, fn ($q) => $q->where('id', '!=', $this->id))->exists();
            } elseif ($this instanceof LeaveRequest && $this->status !== 'Rejected') {
                $duplicate = static::where('employee_id', $this->employee_id)->where('status', '!=', 'Rejected')->whereDate('start_date', '<=', $this->end_date->toDateString())->whereDate('end_date', '>=', $this->start_date->toDateString())->when($this->exists, fn ($q) => $q->where('id', '!=', $this->id))->exists();
            } elseif ($this instanceof PermissionRequest && $this->status !== 'Rejected') {
                $duplicate = static::where('employee_id', $this->employee_id)->where('status', '!=', 'Rejected')->whereDate('permission_date', $this->permission_date)->whereTime('start_time', '<', substr($this->end_time, 0, 5).':00')->whereTime('end_time', '>', substr($this->start_time, 0, 5).':00')->when($this->exists, fn ($q) => $q->where('id', '!=', $this->id))->exists();
            }
            if ($duplicate ?? false) {
                throw ValidationException::withMessages(['request' => 'An overlapping request already exists for this employee.']);
            }

            return parent::save($options);
        }, 3);
    }

    public function delete()
    {
        return DB::transaction(function () {
            Employee::whereKey($this->employee_id)->lockForUpdate()->firstOrFail();

            return parent::delete();
        }, 3);
    }
}
