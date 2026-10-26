<?php

namespace App\Http\Controllers;

use App\Mail\RequestStatusMail;
use App\Mail\RequestApprovalMail;
use App\Models\Employee;
use App\Models\PermissionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PermissionRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $requests = PermissionRequest::with('employee')->when($status, fn ($q) => $q->where('status', $status))->latest()->paginate(20)->withQueryString();
        return view('requests.index', ['requests' => $requests, 'kind' => 'permission', 'status' => $status]);
    }

    public function create(): View
    {
        return view('requests.form', [
            'employees' => Employee::where('employment_status', 'Active')->orderBy('name')->get(),
            'kind' => 'permission',
            'editing' => false,
            'requestRecord' => new PermissionRequest(),
        ]);
    }

    public function edit(PermissionRequest $permissionRequest): View
    {
        return view('requests.form', [
            'employees' => Employee::orderBy('name')->get(),
            'kind' => 'permission',
            'editing' => true,
            'requestRecord' => $permissionRequest,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'permission_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $employee = Employee::findOrFail($data['employee_id']);
        if (blank($employee->email)) {
            throw ValidationException::withMessages(['employee_id' => 'The selected employee does not have an email address.']);
        }

        $data['status'] = 'Pending';
        $permissionRequest = PermissionRequest::create($data);
        $permissionRequest->setRelation('employee', $employee);
        Mail::to($employee->email)->send(new RequestStatusMail($permissionRequest, 'permission'));
        Mail::to(config('requests.md_email'))->send(new RequestApprovalMail($permissionRequest, 'permission'));

        return redirect()->route('permissions.index')->with('success', 'Permission request created and marked pending.');
    }

    public function update(Request $request, PermissionRequest $permissionRequest): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'permission_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        if (blank($employee->email)) {
            throw ValidationException::withMessages(['employee_id' => 'The selected employee does not have an email address.']);
        }

        $permissionRequest->update($data);

        return redirect()->route('permissions.index')->with('success', 'Permission request updated.');
    }

    public function status(Request $request, PermissionRequest $permissionRequest): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['Approved', 'Rejected', 'Pending'])]]);
        $shouldNotify = $permissionRequest->status !== $data['status']
            && in_array($data['status'], ['Approved', 'Rejected'], true);

        if ($shouldNotify) {
            $permissionRequest->loadMissing('employee');
            if (blank($permissionRequest->employee?->email)) {
                throw ValidationException::withMessages(['status' => 'The employee does not have an email address.']);
            }
        }

        $permissionRequest->update(['status' => $data['status'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        if ($shouldNotify) {
            Mail::to($permissionRequest->employee->email)->send(new RequestStatusMail($permissionRequest, 'permission'));
        }

        return back()->with('success', 'Permission request status updated.');
    }

    public function destroy(PermissionRequest $permissionRequest): RedirectResponse
    {
        $permissionRequest->delete();
        return back()->with('success', 'Permission request deleted.');
    }
}
