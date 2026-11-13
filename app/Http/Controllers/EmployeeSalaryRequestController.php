<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeSalaryRequestController extends Controller
{
    private function employee(Request $request): Employee
    {
        return Employee::where('email', $request->user()->email)->where('employment_status', 'Active')->firstOrFail();
    }

    public function create(Request $request, string $kind)
    {
        $employee = $this->employee($request);

        return view('salary.employee-request', compact('employee', 'kind'));
    }

    public function store(Request $request, string $kind)
    {
        $employee = $this->employee($request);
        $request->merge(['employee_id' => $employee->id]);
        if ($kind === 'leave') {
            // Employee self-service is limited to Casual Leave; review stays with Admin/Owner.
            $request->merge(['leave_type' => 'Casual Leave']);
            app(LeaveRequestController::class)->store($request);
        } else {
            app(PermissionRequestController::class)->store($request);
        }

        return redirect()->route($kind === 'leave' ? 'employee.leaves' : 'employee.permissions')->with('success', 'Request submitted for approval.');
    }
}
