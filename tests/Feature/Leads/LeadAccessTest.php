<?php

namespace Tests\Feature\Leads;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Gate;

class LeadAccessTest extends LeadTestCase
{
    public function test_all_module_routes_require_authentication(): void
    {
        auth()->logout();
        foreach (['leads.index', 'leads.dashboard', 'leads.create', 'leads.imports.create', 'leads.imports.index', 'leads.imports.template', 'leads.export'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_admin_can_create_edit_assign_and_change_status_with_audit(): void
    {
        [$user, $employee] = $this->employee();
        $this->post(route('leads.store'), ['lead_name' => 'Customer', 'mobile_number' => '+91 98765 43210', 'location' => 'Chennai', 'requirement' => 'Plot'])->assertSessionHasNoErrors()->assertRedirect();
        $lead = Lead::firstOrFail();
        $this->get(route('leads.create'))->assertOk();
        $this->get(route('leads.edit', $lead))->assertOk();
        $this->get(route('leads.assignment', $lead))->assertOk();
        $this->put(route('leads.update', $lead), ['lead_name' => 'Updated customer', 'mobile_number' => '9876543210', 'location' => 'Madurai', 'requirement' => 'House'])->assertSessionHasNoErrors();
        $this->post(route('leads.assign', $lead), ['assigned_to' => $employee->id, 'reason' => 'Sales territory'])->assertSessionHasNoErrors();
        $this->patch(route('leads.status', $lead), ['status' => 'Inactive', 'reason' => 'Customer requested closure'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => 'Inactive', 'assigned_to' => $employee->id, 'lead_name' => 'Updated customer']);
        $this->assertDatabaseHas('lead_assignments', ['lead_id' => $lead->id, 'new_employee_id' => $employee->id, 'assigned_by' => $this->admin->id]);
        $this->assertDatabaseHas('lead_audits', ['lead_id' => $lead->id, 'event' => 'status_changed']);
        $this->get(route('leads.show', $lead))->assertOk()->assertSee('Customer requested closure')->assertSee('Assignment history');
        $this->get(route('leads.index', ['status' => 'Inactive']))->assertOk()->assertSee('Updated customer');
        $this->delete('/leads/'.$lead->id)->assertStatus(405);
        $this->assertDatabaseCount('leads', 1);
    }

    public function test_employee_cannot_read_or_write_other_employees_leads_even_with_tampered_ids(): void
    {
        [$user, $employee] = $this->employee('First employee');
        [$otherUser, $otherEmployee] = $this->employee('Second employee');
        $own = $this->lead(['assigned_to' => $employee->id, 'lead_name' => 'Own lead']);
        $other = $this->lead(['assigned_to' => $otherEmployee->id, 'lead_name' => 'Other private customer', 'mobile_number' => '8765432109']);
        $this->actingAs($user);
        $this->get(route('leads.index'))->assertOk()->assertSee('Own lead')->assertDontSee('Other private customer');
        foreach (['leads.show', 'leads.edit', 'leads.assignment', 'leads.followup'] as $route) {
            $this->get(route($route, $other))->assertForbidden();
        }
        $this->put(route('leads.update', $other), ['location' => 'Hijacked', 'requirement' => 'Plot'])->assertForbidden();
        $this->post(route('leads.followups.store', $other), ['outcome' => 'RNR', 'followup_at' => now()->toDateTimeString()])->assertForbidden();
        $this->post(route('leads.assign', $other), ['assigned_to' => $employee->id])->assertForbidden();
        $this->patch(route('leads.status', $other), ['status' => 'Active', 'reason' => 'Hijacked'])->assertForbidden();
        $this->get(route('leads.index', ['assigned_to' => $otherEmployee->id]))->assertSessionHasErrors('assigned_to');
        $this->assertDatabaseHas('leads', ['id' => $other->id, 'assigned_to' => $otherEmployee->id, 'location' => 'Chennai']);
    }

    public function test_employee_has_limited_edits_and_no_import_assignment_status_or_export_permissions(): void
    {
        [$user, $employee] = $this->employee();
        $lead = $this->lead(['assigned_to' => $employee->id]);
        $this->actingAs($user);
        $this->get(route('leads.show', $lead))->assertOk();
        $this->get(route('leads.edit', $lead))->assertOk();
        $this->put(route('leads.update', $lead), ['location' => 'Madurai', 'requirement' => 'Land'])->assertSessionHasNoErrors();
        foreach (['assigned_to' => $employee->id, 'status' => 'Inactive', 'mobile_number' => '8765432109', 'created_by' => $user->id, 'lead_name' => 'Changed'] as $field => $value) {
            $this->put(route('leads.update', $lead), ['location' => 'Madurai', 'requirement' => 'Land', $field => $value])->assertSessionHasErrors($field);
        }
        foreach (['leads.create', 'leads.imports.create', 'leads.imports.index', 'leads.imports.template', 'leads.export'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
        $this->post(route('leads.imports.store'), ['file' => $this->csv('test')])->assertForbidden();
        $this->post(route('leads.store'), [])->assertForbidden();
        $this->post(route('leads.assign', $lead), ['assigned_to' => $employee->id])->assertForbidden();
        $this->patch(route('leads.status', $lead), ['status' => 'Inactive', 'reason' => 'No access'])->assertForbidden();
    }

    public function test_owner_is_read_only_until_management_permissions_are_explicitly_enabled(): void
    {
        $lead = $this->lead();
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);
        foreach (['leads.index', 'leads.dashboard', 'leads.imports.index'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('leads.show', $lead))->assertOk()->assertSee('9876543210');
        $this->get(route('leads.export'))->assertOk();
        $this->get(route('leads.edit', $lead))->assertForbidden();
        $this->post(route('leads.assign', $lead), [])->assertForbidden();
        $this->patch(route('leads.status', $lead), [])->assertForbidden();
        $this->post(route('leads.imports.store'), [])->assertForbidden();
        config(['leads.permissions.owner' => ['mobile', 'assign', 'status']]);
        $this->assertTrue(Gate::allows('assign', $lead));
        $this->patch(route('leads.status', $lead), ['status' => 'Inactive', 'reason' => 'Owner permission enabled'])->assertSessionHasNoErrors();
    }

    public function test_masking_applies_to_html_edit_pages_serialization_and_search(): void
    {
        [$user, $employee] = $this->employee();
        $lead = $this->lead(['assigned_to' => $employee->id, 'source_data' => ['MOBILE NO' => '9876543210']]);
        $this->actingAs($user);
        foreach (['leads.index' => null, 'leads.show' => $lead, 'leads.edit' => $lead, 'leads.followup' => $lead] as $route => $argument) {
            $response = $this->get(route($route, $argument))->assertOk()->assertSee('******3210')->assertDontSee('9876543210');
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        }
        $this->get(route('leads.index', ['search' => '9876543210']))->assertOk()->assertViewHas('leads', fn ($leads) => $leads->total() === 0);
        $this->assertArrayNotHasKey('normalized_mobile', $lead->toArray());
        $this->assertArrayNotHasKey('source_data', $lead->toArray());
    }

    public function test_reassignment_and_inactive_employee_remove_access(): void
    {
        [$user, $employee] = $this->employee();
        [$secondUser, $secondEmployee] = $this->employee();
        $lead = $this->lead(['assigned_to' => $employee->id]);
        $this->post(route('leads.assign', $lead), ['assigned_to' => $secondEmployee->id])->assertSessionHasNoErrors();
        $this->actingAs($user)->get(route('leads.show', $lead))->assertForbidden();
        $this->actingAs($secondUser)->get(route('leads.show', $lead))->assertOk();
        $secondEmployee->update(['employment_status' => 'Inactive']);
        $this->get(route('leads.show', $lead))->assertForbidden();
        $this->actingAs($this->admin)->post(route('leads.assign', $lead), ['assigned_to' => $secondEmployee->id])->assertSessionHasErrors('assigned_to');
    }

    public function test_validation_duplicate_mobile_and_stored_xss_are_handled(): void
    {
        $lead = $this->lead(['lead_name' => '<script>alert(1)</script>', 'location' => '<img src=x onerror=alert(2)>']);
        $this->get(route('leads.show', $lead))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->post(route('leads.store'), ['lead_name' => 'Duplicate', 'mobile_number' => '+91 9876543210', 'location' => 'Chennai', 'requirement' => 'Plot'])->assertSessionHasErrors('mobile_number');
        $this->post(route('leads.store'), ['lead_name' => 'Missing', 'mobile_number' => '123'])->assertSessionHasErrors(['mobile_number', 'location', 'requirement']);
        $this->get(route('leads.index', ['search' => "' OR 1=1 --"]))->assertOk()->assertViewHas('leads', fn ($leads) => $leads->total() === 0);
        $this->assertDatabaseCount('leads', 1);
    }

    public function test_web_routes_enforce_csrf_when_test_bypass_is_removed(): void
    {
        $middleware = new class($this->app, $this->app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        };
        $this->app->instance(ValidateCsrfToken::class, $middleware);
        $this->post(route('leads.store'), ['lead_name' => 'No token'])->assertStatus(419);
        $this->assertDatabaseCount('leads', 0);
    }
}
