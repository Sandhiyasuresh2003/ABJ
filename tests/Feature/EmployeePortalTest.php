<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PermissionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeePortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_sign_in_and_is_redirected_to_their_workspace(): void
    {
        $employee = User::factory()->create([
            'email' => 'worker@example.com',
            'role' => 'employee',
            'password' => 'employee-password',
        ]);

        $this->post(route('login.submit'), [
            'email' => $employee->email,
            'password' => 'employee-password',
        ])->assertRedirect(route('employee.workspace'));

        $this->assertAuthenticatedAs($employee);
        $this->get(route('employee.workspace'))->assertOk()->assertSee('Employee workspace');
        $this->get('/')->assertRedirect(route('employee.workspace'));
    }

    public function test_owner_can_sign_in_and_use_the_full_admin_workspace(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'password' => 'owner-password',
        ]);

        $this->post(route('login.submit'), [
            'email' => $owner->email,
            'password' => 'owner-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($owner);
        $this->get(route('dashboard'))->assertOk();
        $this->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_employee_can_only_view_their_own_attendance_leave_and_permission_records(): void
    {
        $user = User::factory()->create(['email' => 'worker@example.com', 'role' => 'employee']);
        $employee = $this->createEmployee('worker@example.com', 'Worker');
        $otherEmployee = $this->createEmployee('other@example.com', 'Other worker');

        Attendance::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-22',
            'status' => 'Present',
            'notes' => 'My own attendance note',
        ]);
        Attendance::create([
            'employee_id' => $otherEmployee->id,
            'attendance_date' => '2026-10-22',
            'status' => 'Absent',
            'notes' => 'Other worker private note',
        ]);
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type' => 'Sick Leave',
            'start_date' => '2026-10-23',
            'end_date' => '2026-10-24',
            'reason' => 'My own leave reason',
            'status' => 'Pending',
        ]);
        LeaveRequest::create([
            'employee_id' => $otherEmployee->id,
            'leave_type' => 'Earned Leave',
            'start_date' => '2026-10-23',
            'end_date' => '2026-10-24',
            'reason' => 'Other worker private leave reason',
            'status' => 'Approved',
        ]);
        PermissionRequest::create([
            'employee_id' => $employee->id,
            'permission_date' => '2026-10-22',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'reason' => 'My own permission reason',
            'status' => 'Approved',
        ]);
        PermissionRequest::create([
            'employee_id' => $otherEmployee->id,
            'permission_date' => '2026-10-22',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'reason' => 'Other worker private permission reason',
            'status' => 'Rejected',
        ]);

        $this->actingAs($user);
        $this->get(route('employee.attendance'))
            ->assertOk()
            ->assertSee('My own attendance note')
            ->assertDontSee('Other worker private note');
        $this->get(route('employee.leaves'))
            ->assertOk()
            ->assertSee('My own leave reason')
            ->assertSee('Pending')
            ->assertDontSee('Other worker private leave reason');
        $this->get(route('employee.permissions'))
            ->assertOk()
            ->assertSee('My own permission reason')
            ->assertSee('Approved')
            ->assertDontSee('Other worker private permission reason');
    }

    public function test_employee_cannot_access_admin_pages_or_mutate_their_records(): void
    {
        $user = User::factory()->create(['email' => 'worker@example.com', 'role' => 'employee']);
        $employee = $this->createEmployee('worker@example.com', 'Worker');
        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-22',
            'status' => 'Present',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();
        $this->get(route('attendance.index'))->assertForbidden();
        $this->get(route('leaves.index'))->assertForbidden();
        $this->delete(route('attendance.destroy', $attendance))->assertForbidden();
    }

    private function createEmployee(string $email, string $name): Employee
    {
        return Employee::create([
            'employee_code' => strtoupper(substr(md5($email), 0, 8)),
            'name' => $name,
            'email' => $email,
            'department' => 'Sales',
            'designation' => 'Executive',
            'joining_date' => '2026-01-01',
            'employment_status' => 'Active',
        ]);
    }
}
