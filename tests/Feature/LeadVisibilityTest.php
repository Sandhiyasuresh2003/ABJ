<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_only_sees_leads_assigned_to_their_employee_profile(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $employeeUser = User::factory()->create(['role' => 'employee']);
        $otherEmployeeUser = User::factory()->create(['role' => 'employee']);
        $employee = $this->createEmployee($employeeUser);
        $otherEmployee = $this->createEmployee($otherEmployeeUser);

        $assignedLead = $this->createLead($owner, $employee, '9876543210', 'Assigned to this employee');
        $otherLead = $this->createLead($owner, $otherEmployee, '9876543211', 'Assigned to another employee');
        $unassignedLead = $this->createLead($owner, null, '9876543212', 'Unassigned lead');

        $this->actingAs($employeeUser)
            ->get(route('leads.index'))
            ->assertOk()
            ->assertSee('My assigned leads')
            ->assertSee($assignedLead->lead_name)
            ->assertDontSee($otherLead->lead_name)
            ->assertDontSee($unassignedLead->lead_name);

        $this->get(route('leads.show', $assignedLead))->assertOk();
        $this->get(route('leads.show', $otherLead))->assertForbidden();
        $this->get(route('leads.show', $unassignedLead))->assertForbidden();
    }

    public function test_owner_can_see_and_open_all_leads(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $employeeUser = User::factory()->create(['role' => 'employee']);
        $employee = $this->createEmployee($employeeUser);

        $assignedLead = $this->createLead($owner, $employee, '9876543213', 'Assigned lead');
        $unassignedLead = $this->createLead($owner, null, '9876543214', 'Unassigned lead');

        $this->actingAs($owner)
            ->get(route('leads.index'))
            ->assertOk()
            ->assertSee('All leads')
            ->assertSee($assignedLead->lead_name)
            ->assertSee($unassignedLead->lead_name);

        $this->get(route('leads.show', $assignedLead))->assertOk();
        $this->get(route('leads.show', $unassignedLead))->assertOk();
    }

    private function createEmployee(User $user): Employee
    {
        return Employee::query()->create([
            'employee_code' => 'EMP'.$user->id,
            'name' => $user->name,
            'email' => $user->email,
            'department' => 'Sales',
            'designation' => 'Sales Executive',
            'joining_date' => now()->toDateString(),
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
}
