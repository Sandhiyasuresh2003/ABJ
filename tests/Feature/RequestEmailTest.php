<?php

namespace Tests\Feature;

use App\Mail\RequestStatusMail;
use App\Mail\RequestApprovalMail;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PermissionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RequestEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_leave_creation_and_approval_email_the_selected_employee(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $employee = $this->createEmployee();

        $this->post(route('leaves.store'), [
            'employee_id' => $employee->id,
            'leave_type' => 'Casual Leave',
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-03',
            'reason' => 'Personal matter',
        ])->assertRedirect(route('leaves.index'));

        $leaveRequest = LeaveRequest::firstOrFail();
        $this->assertSame('Pending', $leaveRequest->status);
        Mail::assertSent(RequestStatusMail::class, function (RequestStatusMail $mail) use ($employee): bool {
            return $mail->hasTo($employee->email)
                && $mail->requestType === 'leave'
                && $mail->request->status === 'Pending'
                && ! str_contains($mail->render(), 'request-review')
                && str_contains($mail->render(), 'Casual Leave');
        });
        Mail::assertSent(RequestApprovalMail::class, fn (RequestApprovalMail $mail) => $mail->hasTo('kavimdu3784@gmail.com')
            && $mail->requestType === 'leave'
            && str_contains($mail->render(), 'Approve')
            && str_contains($mail->render(), 'Reject'));

        $this->patch(route('leaves.status', $leaveRequest), ['status' => 'Approved'])
            ->assertRedirect();

        Mail::assertSent(RequestStatusMail::class, 2);
        Mail::assertSent(RequestStatusMail::class, fn (RequestStatusMail $mail) => $mail->hasTo($employee->email)
            && $mail->requestType === 'leave'
            && $mail->request->status === 'Approved');

        $this->patch(route('leaves.status', $leaveRequest), ['status' => 'Approved'])
            ->assertRedirect();
        Mail::assertSent(RequestStatusMail::class, 2);
    }

    public function test_permission_creation_and_rejection_email_the_selected_employee(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $employee = $this->createEmployee();

        $this->post(route('permissions.store'), [
            'employee_id' => $employee->id,
            'permission_date' => '2026-11-04',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'reason' => 'Personal appointment',
        ])->assertRedirect(route('permissions.index'));

        $permissionRequest = PermissionRequest::firstOrFail();
        $this->assertSame('Pending', $permissionRequest->status);
        Mail::assertSent(RequestStatusMail::class, function (RequestStatusMail $mail) use ($employee): bool {
            return $mail->hasTo($employee->email)
                && $mail->requestType === 'permission'
                && $mail->request->status === 'Pending'
                && ! str_contains($mail->render(), 'request-review')
                && str_contains($mail->render(), 'permission request');
        });
        Mail::assertSent(RequestApprovalMail::class, fn (RequestApprovalMail $mail) => $mail->hasTo('kavimdu3784@gmail.com')
            && $mail->requestType === 'permission'
            && str_contains($mail->render(), 'Approve')
            && str_contains($mail->render(), 'Reject'));

        $this->patch(route('permissions.status', $permissionRequest), ['status' => 'Rejected'])
            ->assertRedirect();

        Mail::assertSent(RequestStatusMail::class, 2);
        Mail::assertSent(RequestStatusMail::class, fn (RequestStatusMail $mail) => $mail->hasTo($employee->email)
            && $mail->requestType === 'permission'
            && $mail->request->status === 'Rejected');
    }

    public function test_request_cannot_be_created_without_an_employee_email(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $employee = $this->createEmployee(email: null);

        $this->from(route('leaves.create'))->post(route('leaves.store'), [
            'employee_id' => $employee->id,
            'leave_type' => 'Casual Leave',
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-03',
            'reason' => 'Personal matter',
        ])->assertSessionHasErrors('employee_id');

        $this->assertDatabaseCount('leave_requests', 0);
        Mail::assertNothingSent();
    }

    public function test_status_cannot_be_approved_without_an_employee_email(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $employee = $this->createEmployee(email: null);
        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type' => 'Casual Leave',
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-03',
            'reason' => 'Personal matter',
            'status' => 'Pending',
        ]);

        $this->from(route('leaves.index'))->patch(route('leaves.status', $leaveRequest), ['status' => 'Approved'])
            ->assertSessionHasErrors('status');

        $this->assertSame('Pending', $leaveRequest->fresh()->status);
        Mail::assertNothingSent();
    }

    private function createEmployee(?string $email = 'employee@example.com'): Employee
    {
        return Employee::create([
            'employee_code' => 'EMP-001',
            'name' => 'Test Employee',
            'email' => $email,
            'department' => 'Administration',
            'designation' => 'Assistant',
            'joining_date' => '2024-01-01',
            'employment_status' => 'Active',
        ]);
    }
}
