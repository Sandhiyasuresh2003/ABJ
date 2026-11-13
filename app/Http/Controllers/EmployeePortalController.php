<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PermissionRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeePortalController extends Controller
{
    public function workspace(Request $request): View
    {
        $employee = $this->employeeFor($request);

        return view('employee.workspace', [
            'employee' => $employee,
            'attendanceCount' => $employee ? Attendance::where('employee_id', $employee->id)->count() : 0,
            'leaveCount' => $employee ? LeaveRequest::where('employee_id', $employee->id)->count() : 0,
            'permissionCount' => $employee ? PermissionRequest::where('employee_id', $employee->id)->count() : 0,
        ]);
    }

    public function attendance(Request $request): View
    {
        $employee = $this->employeeFor($request);
        $records = Attendance::query()
            ->where('employee_id', $employee?->id ?? 0)
            ->latest('attendance_date')
            ->paginate(20)
            ->withQueryString();

        return view('employee.records', [
            'employee' => $employee,
            'records' => $records,
            'kind' => 'attendance',
            'title' => 'My attendance',
        ]);
    }

    public function leaves(Request $request): View
    {
        $employee = $this->employeeFor($request);
        $records = LeaveRequest::query()
            ->where('employee_id', $employee?->id ?? 0)
            ->latest('start_date')
            ->paginate(20)
            ->withQueryString();

        return view('employee.records', [
            'employee' => $employee,
            'records' => $records,
            'kind' => 'leave',
            'title' => 'My leave requests',
        ]);
    }

    public function permissions(Request $request): View
    {
        $employee = $this->employeeFor($request);
        $records = PermissionRequest::query()
            ->where('employee_id', $employee?->id ?? 0)
            ->latest('permission_date')
            ->paginate(20)
            ->withQueryString();

        return view('employee.records', [
            'employee' => $employee,
            'records' => $records,
            'kind' => 'permission',
            'title' => 'My permission requests',
        ]);
    }

    private function employeeFor(Request $request): ?Employee
    {
        return Employee::query()->where('email', $request->user()->email)->first();
    }
}
