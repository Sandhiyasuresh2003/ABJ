<?php

namespace Tests\Feature;

use App\Mail\RequestApprovalMail;
use App\Mail\RequestStatusMail;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PermissionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class RequestReviewTest extends TestCase
{
    use RefreshDatabase;

    public static function decisions(): array
    {
        return [
            ['leave', 'approve', 'Approved'],
            ['leave', 'reject', 'Rejected'],
            ['permission', 'approve', 'Approved'],
            ['permission', 'reject', 'Rejected'],
        ];
    }

    #[DataProvider('decisions')]
    public function test_md_can_confirm_decision_without_login_and_employee_is_notified_once(string $kind, string $decision, string $status): void
    {
        Mail::fake();
        $owner = User::factory()->create(['email' => config('requests.md_email')]);
        $item = $this->createRequest($kind);
        $mail = new RequestApprovalMail($item, $kind);
        $url = $decision === 'approve' ? $mail->approveUrl : $mail->rejectUrl;

        // Email scanners and previews must never change state.
        $this->get($url)->assertOk()->assertSee('Confirm '.($decision === 'approve' ? 'approval' : 'rejection'));
        $this->assertSame('Pending', $item->fresh()->status);
        Mail::assertNothingSent();

        $this->post($url)->assertOk()->assertSee('already been reviewed');
        $item->refresh();
        $this->assertSame($status, $item->status);
        $this->assertSame($owner->id, $item->reviewed_by);
        $this->assertNotNull($item->reviewed_at);
        Mail::assertSent(RequestStatusMail::class, fn (RequestStatusMail $sent) => $sent->hasTo($item->employee->email)
            && $sent->requestType === $kind && $sent->request->status === $status
            && ! str_contains($sent->render(), 'request-review'));

        $this->post($url)->assertOk();
        $this->post($decision === 'approve' ? $mail->rejectUrl : $mail->approveUrl)->assertOk();
        $this->assertSame($status, $item->fresh()->status);
        Mail::assertSent(RequestStatusMail::class, 1);

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get(route($kind === 'leave' ? 'leaves.index' : 'permissions.index'))
            ->assertOk()->assertSee($status);
    }

    public function test_unsigned_tampered_and_expired_links_cannot_review_a_request(): void
    {
        Mail::fake();
        $item = $this->createRequest('leave');
        $parameters = ['kind' => 'leave', 'id' => $item->id, 'decision' => 'approve'];
        $unsigned = route('requests.review', $parameters);
        $valid = URL::temporarySignedRoute('requests.review', now()->addDay(), $parameters);
        $expired = URL::temporarySignedRoute('requests.review', now()->subMinute(), $parameters);

        foreach ([$unsigned, str_replace('/approve?', '/reject?', $valid), $expired] as $url) {
            $this->get($url)->assertForbidden();
            $this->post($url)->assertForbidden();
        }

        $this->assertSame('Pending', $item->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_smtp_failure_keeps_request_pending_and_allows_retry(): void
    {
        $item = $this->createRequest('permission');
        $url = (new RequestApprovalMail($item, 'permission'))->approveUrl;
        Mail::shouldReceive('to')->once()->with($item->employee->email)->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new TransportException('SMTP unavailable'));

        $this->post($url)->assertOk()->assertSee('Your decision was not saved');
        $this->assertSame('Pending', $item->fresh()->status);
        $this->assertNull($item->fresh()->reviewed_at);

        Mail::swap(new MailManager($this->app));
        Mail::fake();
        $this->post($url)->assertOk()->assertSee('already been reviewed');
        $this->assertSame('Approved', $item->fresh()->status);
        Mail::assertSent(RequestStatusMail::class, 1);
    }

    public function test_review_requires_employee_email(): void
    {
        Mail::fake();
        $item = $this->createRequest('leave');
        $item->employee->update(['email' => null]);
        $url = (new RequestApprovalMail($item, 'leave'))->approveUrl;

        $this->post($url)->assertStatus(422);
        $this->assertSame('Pending', $item->fresh()->status);
        Mail::assertNothingSent();
    }

    private function createRequest(string $kind): LeaveRequest|PermissionRequest
    {
        $employee = Employee::create([
            'employee_code' => 'EMP-001', 'name' => 'Test Employee', 'email' => 'employee@example.com',
            'department' => 'Administration', 'designation' => 'Assistant',
            'joining_date' => '2024-01-01', 'employment_status' => 'Active',
        ]);
        $data = ['employee_id' => $employee->id, 'reason' => 'Appointment', 'status' => 'Pending'];

        return $kind === 'leave'
            ? LeaveRequest::create($data + ['leave_type' => 'Casual Leave', 'start_date' => '2026-11-02', 'end_date' => '2026-11-03'])
            : PermissionRequest::create($data + ['permission_date' => '2026-11-02', 'start_time' => '10:00', 'end_time' => '11:00']);
    }
}
