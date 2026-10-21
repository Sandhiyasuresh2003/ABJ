<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class EmployeeController extends Controller
{
    private function departments(): array
    {
        return ['Administration', 'Accounts', 'Sales', 'Marketing', 'Site Operations', 'Customer Support', 'Human Resources', 'Other'];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $employees = Employee::query()->when($search, function ($query, $search) {
            $query->where(fn ($q) => $q->where('employee_code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")->orWhere('department', 'like', "%{$search}%"));
        })->latest()->paginate(15)->withQueryString();

        return view('employees.index', compact('employees', 'search'));
    }

    public function create(): View { return view('employees.form', ['employee' => new Employee(), 'departments' => $this->departments(), 'editing' => false]); }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_code' => ['required', 'string', 'max:30', 'unique:employees,employee_code'],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', 'unique:employees,email'],
            'phone' => ['nullable', 'string', 'max:25'],
            'department' => ['required', Rule::in($this->departments())],
            'designation' => ['required', 'string', 'max:100'],
            'joining_date' => ['required', 'date', 'before_or_equal:today'],
            'employment_status' => ['required', Rule::in(['Active', 'Inactive'])],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);
        Employee::create($data);
        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function edit(Employee $employee): View { return view('employees.form', ['employee' => $employee, 'departments' => $this->departments(), 'editing' => true]); }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'employee_code' => ['required', 'string', 'max:30', Rule::unique('employees', 'employee_code')->ignore($employee->id)],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('employees', 'email')->ignore($employee->id)],
            'phone' => ['nullable', 'string', 'max:25'],
            'department' => ['required', Rule::in($this->departments())],
            'designation' => ['required', 'string', 'max:100'],
            'joining_date' => ['required', 'date', 'before_or_equal:today'],
            'employment_status' => ['required', Rule::in(['Active', 'Inactive'])],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);
        $employee->update($data);
        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }
}
