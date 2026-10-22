<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Notifications\AccountSetupNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    private function departments(): array
    {
        return ['Administration', 'Accounts', 'Sales', 'Marketing', 'Site Operations', 'Customer Support', 'Human Resources', 'Other'];
    }

    private function roles(): array
    {
        return array_keys(config('sidebar.roles'));
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

    public function create(): View
    {
        return view('employees.form', [
            'employee' => new Employee(),
            'departments' => $this->departments(),
            'roles' => $this->roles(),
            'selectedRole' => 'employee',
            'editing' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_code' => ['required', 'string', 'max:30', 'unique:employees,employee_code'],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:employees,email'],
            'role' => ['required', Rule::in($this->roles())],
            'phone' => ['nullable', 'string', 'max:25'],
            'department' => ['required', Rule::in($this->departments())],
            'designation' => ['required', 'string', 'max:100'],
            'joining_date' => ['required', 'date', 'before_or_equal:today'],
            'employment_status' => ['required', Rule::in(['Active', 'Inactive'])],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($data): void {
            $employee = Employee::create(collect($data)->except('role')->all());
            $this->assignRole($employee, $data['role']);
        });

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function edit(Employee $employee): View
    {
        return view('employees.form', [
            'employee' => $employee,
            'departments' => $this->departments(),
            'roles' => $this->roles(),
            'selectedRole' => User::query()->where('email', $employee->email)->value('role') ?? 'employee',
            'editing' => true,
        ]);
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $linkedUser = User::query()->where('email', $employee->email)->first();
        $targetUser = User::query()->where('email', $request->input('email'))->first();
        $emailOwnerId = $linkedUser?->id ?? $targetUser?->id;

        $data = $request->validate([
            'employee_code' => ['required', 'string', 'max:30', Rule::unique('employees', 'employee_code')->ignore($employee->id)],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('employees', 'email')->ignore($employee->id), Rule::unique('users', 'email')->ignore($emailOwnerId)],
            'role' => ['required', Rule::in($this->roles())],
            'phone' => ['nullable', 'string', 'max:25'],
            'department' => ['required', Rule::in($this->departments())],
            'designation' => ['required', 'string', 'max:100'],
            'joining_date' => ['required', 'date', 'before_or_equal:today'],
            'employment_status' => ['required', Rule::in(['Active', 'Inactive'])],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($employee, $data, $linkedUser): void {
            $employee->update(collect($data)->except('role')->all());
            $this->assignRole($employee, $data['role'], $linkedUser);
        });

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }

    private function assignRole(Employee $employee, string $role, ?User $linkedUser = null): void
    {
        if ($linkedUser) {
            $linkedUser->email = $employee->email;
            $linkedUser->role = $role;
            $linkedUser->save();

            return;
        }

        $user = User::query()->where('email', $employee->email)->first();

        if ($user) {
            $user->update(['role' => $role]);

            return;
        }

        $user = User::create([
            'name' => $employee->name,
            'email' => $employee->email,
            'password' => Str::random(64),
            'role' => $role,
        ]);

        $token = Str::random(64);
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        $setupUrl = URL::temporarySignedRoute(
            'account.password.setup',
            now()->addMinutes(config('auth.passwords.users.expire')),
            ['token' => $token, 'email' => $user->email]
        );

        $user->notify(new AccountSetupNotification($setupUrl));
    }
}
