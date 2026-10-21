<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->query('date', now()->toDateString());
        $records = Attendance::with('employee')->whereDate('attendance_date', $date)->latest()->paginate(20)->withQueryString();
        return view('attendance.index', compact('records', 'date'));
    }

    public function create(): View
    {
        return view('attendance.form', ['record' => new Attendance(), 'employees' => Employee::where('employment_status', 'Active')->orderBy('name')->get(), 'editing' => false]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Attendance::create($data);
        return redirect()->route('attendance.index', ['date' => $data['attendance_date']])->with('success', 'Attendance saved.');
    }

    public function edit(Attendance $attendance): View
    {
        return view('attendance.form', ['record' => $attendance, 'employees' => Employee::orderBy('name')->get(), 'editing' => true]);
    }

    public function update(Request $request, Attendance $attendance): RedirectResponse
    {
        $data = $this->validated($request, $attendance);
        $attendance->update($data);
        return redirect()->route('attendance.index', ['date' => $data['attendance_date']])->with('success', 'Attendance updated.');
    }

    public function destroy(Attendance $attendance): RedirectResponse
    {
        $attendance->delete();
        return back()->with('success', 'Attendance record deleted.');
    }

    private function validated(Request $request, ?Attendance $attendance = null): array
    {
        return $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'attendance_date' => [
                'required', 'date',
                Rule::unique('attendances', 'attendance_date')->where(fn ($q) => $q->where('employee_id', $request->input('employee_id')))->ignore($attendance?->id),
            ],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i', 'after:check_in'],
            'status' => ['required', Rule::in(['Present', 'Late', 'Absent', 'Half Day', 'Leave'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
