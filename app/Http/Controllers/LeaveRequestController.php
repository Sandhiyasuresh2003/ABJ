<?php

namespace App\Http\Controllers;

use App\Mail\RequestStatusMail;
use App\Mail\RequestApprovalMail;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class LeaveRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $requests = LeaveRequest::with('employee')->when($status, fn ($q) => $q->where('status', $status))->latest()->paginate(20)->withQueryString();
        return view('requests.index', ['requests' => $requests, 'kind' => 'leave', 'status' => $status]);
    }

    public function create(): View
    {
        return view('requests.form', [
            'employees' => Employee::where('employment_status', 'Active')->orderBy('name')->get(),
            'kind' => 'leave',
            'editing' => false,
            'requestRecord' => new LeaveRequest(),
        ]);
    }

    public function edit(LeaveRequest $leaveRequest): View
    {
        return view('requests.form', [
            'employees' => Employee::orderBy('name')->get(),
            'kind' => 'leave',
            'editing' => true,
            'requestRecord' => $leaveRequest,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type' => ['required', Rule::in(['Casual Leave', 'Sick Leave', 'Earned Leave', 'Unpaid Leave', 'Other'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $employee = Employee::findOrFail($data['employee_id']);
        if (blank($employee->email)) {
            throw ValidationException::withMessages(['employee_id' => 'The selected employee does not have an email address.']);
        }

        $data['status'] = 'Pending';
        $leaveRequest = LeaveRequest::create($data);
        $leaveRequest->setRelation('employee', $employee);
        Mail::to($employee->email)->send(new RequestStatusMail($leaveRequest, 'leave'));
        Mail::to(config('requests.md_email'))->send(new RequestApprovalMail($leaveRequest, 'leave'));

        return redirect()->route('leaves.index')->with('success', 'Leave request created and marked pending.');
    }

    public function update(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'leave_type' => ['required', Rule::in(['Casual Leave', 'Sick Leave', 'Earned Leave', 'Unpaid Leave', 'Other'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        if (blank($employee->email)) {
            throw ValidationException::withMessages(['employee_id' => 'The selected employee does not have an email address.']);
        }

        $leaveRequest->update($data);

        return redirect()->route('leaves.index')->with('success', 'Leave request updated.');
    }

    public function status(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['Approved', 'Rejected', 'Pending'])]]);
        $shouldNotify = $leaveRequest->status !== $data['status']
            && in_array($data['status'], ['Approved', 'Rejected'], true);

        if ($shouldNotify) {
            $leaveRequest->loadMissing('employee');
            if (blank($leaveRequest->employee?->email)) {
                throw ValidationException::withMessages(['status' => 'The employee does not have an email address.']);
            }
        }

        $leaveRequest->update(['status' => $data['status'], 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        if ($shouldNotify) {
            Mail::to($leaveRequest->employee->email)->send(new RequestStatusMail($leaveRequest, 'leave'));
        }

        return back()->with('success', 'Leave request status updated.');
    }

    public function destroy(LeaveRequest $leaveRequest): RedirectResponse
    {
        $leaveRequest->delete();
        return back()->with('success', 'Leave request deleted.');
    }
}
