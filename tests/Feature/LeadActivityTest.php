<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Lead;
use App\Models\SiteVisit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LeadActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_completed_site_visits_are_append_only_and_clients_can_be_marked_booked_once(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-13 12:00:00'));
        $owner = User::factory()->create(['role' => 'owner']);
        $lead = $this->createLead($owner, null, '9876543210', 'Booking test lead');

        $this->actingAs($owner)
            ->post(route('leads.site-visits.store', $lead), [
                'visit_date' => today()->toDateString(),
                'details' => 'Customer visited the property.',
            ])
            ->assertRedirect(route('leads.show', $lead))
            ->assertSessionHasNoErrors();

        $visit = SiteVisit::query()->where('lead_id', $lead->id)->firstOrFail();
        $this->assertSame(today()->toDateString(), $visit->visit_date->toDateString());
        $this->assertSame('Customer visited the property.', $visit->details);

        $this->get(route('leads.show', $lead))
            ->assertOk()
            ->assertSee('Customer visited the property.')
            ->assertSee('cannot be edited or deleted');

        $siteVisitRoute = route('leads.site-visits.store', $lead);
        $this->put($siteVisitRoute, ['details' => 'Changed'])->assertStatus(405);
        $this->delete($siteVisitRoute)->assertStatus(405);
        $this->assertDatabaseHas('site_visits', ['id' => $visit->id, 'details' => 'Customer visited the property.']);

        $this->post(route('leads.book', $lead), ['booked_at' => today()->toDateString()])
            ->assertRedirect(route('leads.show', $lead))
            ->assertSessionHasNoErrors();
        $lead->refresh();
        $this->assertSame(today()->toDateString(), $lead->booked_at->toDateString());
        $this->assertSame($owner->id, $lead->booked_by);

        $this->from(route('leads.show', $lead))
            ->post(route('leads.book', $lead), ['booked_at' => today()->toDateString()])
            ->assertRedirect(route('leads.show', $lead))
            ->assertSessionHasErrors('booked_at');
    }

    public function test_dashboard_chart_uses_six_months_of_activity_visible_to_the_employee(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-13 12:00:00'));
        $owner = User::factory()->create(['role' => 'owner']);
        $employeeUser = User::factory()->create(['role' => 'employee']);
        $otherEmployeeUser = User::factory()->create(['role' => 'employee']);
        $employee = $this->createEmployee($employeeUser);
        $otherEmployee = $this->createEmployee($otherEmployeeUser);

        $assignedLead = $this->createLead($owner, $employee, '9876543211', 'Assigned chart lead');
        $otherLead = $this->createLead($owner, $otherEmployee, '9876543212', 'Other chart lead');
        $assignedLead->update(['booked_at' => '2026-09-10', 'booked_by' => $owner->id]);
        $otherLead->update(['booked_at' => '2026-09-11', 'booked_by' => $owner->id]);

        $this->createSiteVisit($assignedLead, $owner, '2026-09-10');
        $this->createSiteVisit($assignedLead, $owner, '2026-04-30');
        $this->createSiteVisit($otherLead, $owner, '2026-09-11');

        $this->actingAs($employeeUser)
            ->post(route('leads.site-visits.store', $otherLead), ['visit_date' => '2026-09-11'])
            ->assertForbidden();
        $this->post(route('leads.book', $otherLead), ['booked_at' => '2026-09-11'])
            ->assertForbidden();

        $this->get(route('leads.dashboard'))
            ->assertOk()
            ->assertSee('Sep 2026: 1 completed site visits, 1 booked clients', false)
            ->assertSee('Completed site visits')
            ->assertSee('Booked clients');
    }

    private function createEmployee(User $user): Employee
    {
        return Employee::query()->create([
            'employee_code' => 'EMP'.$user->id,
            'name' => $user->name,
            'email' => $user->email,
            'department' => 'Sales',
            'designation' => 'Sales Executive',
            'joining_date' => today()->toDateString(),
            'employment_status' => 'Active',
        ]);
    }

    private function createLead(User $creator, ?Employee $employee, string $mobile, string $name): Lead
    {
        return Lead::query()->create([
            'lead_name' => $name,
            'mobile_number' => $mobile,
            'normalized_mobile' => $mobile,
            'location' => 'Coimbatore',
            'requirement' => 'Residential plot',
            'project_category' => 'Plots',
            'assigned_to' => $employee?->id,
            'status' => 'Active',
            'created_by' => $creator->id,
        ]);
    }

    private function createSiteVisit(Lead $lead, User $creator, string $visitDate): SiteVisit
    {
        return $lead->siteVisits()->create([
            'visit_date' => $visitDate,
            'created_by' => $creator->id,
        ]);
    }
}
