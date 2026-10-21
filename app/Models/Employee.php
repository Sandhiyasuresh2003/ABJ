<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'employee_code', 'name', 'email', 'phone', 'department',
        'designation', 'joining_date', 'employment_status', 'address',
    ];

    protected function casts(): array
    {
        return ['joining_date' => 'date'];
    }

    public function attendanceRecords(): HasMany { return $this->hasMany(Attendance::class); }
    public function leaveRequests(): HasMany { return $this->hasMany(LeaveRequest::class); }
    public function permissionRequests(): HasMany { return $this->hasMany(PermissionRequest::class); }
}
