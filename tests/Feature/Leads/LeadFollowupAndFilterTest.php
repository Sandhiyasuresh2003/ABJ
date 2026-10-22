<?php

namespace Tests\Feature\Leads;

class LeadFollowupAndFilterTest extends LeadTestCase
{
    public function test_followups_are_preserved_and_latest_event_time_determines_current_details(): void
    {
        [$user, $employee] = $this->employee();
        $lead = $this->lead(['assigned_to' => $employee->id]);
        $lead->forceFill(['created_at' => now()->subDays(3)])->save();
        $this->actingAs($user);
        $this->get(route('leads.followup', $lead))->assertOk();
        $this->post(route('leads.followups.store', $lead), ['outcome' => 'Another Call', 'followup_at' => now()->subHour()->toDateTimeString(), 'next_followup_at' => now()->addDay()->toDateTimeString(), 'remarks' => 'Latest callback'])->assertSessionHasNoErrors();
        $this->post(route('leads.followups.store', $lead), ['outcome' => 'Not Interested', 'followup_at' => now()->subDay()->toDateTimeString(), 'remarks' => 'Older historical note'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('lead_followups', 2);
        $this->assertSame('Another Call', $lead->fresh()->lastFollowup->outcome);
        $this->assertSame('Active', $lead->fresh()->status);
        $this->get(route('leads.show', $lead))->assertOk()->assertSeeInOrder(['Older historical note', 'Latest callback']);
        $this->assertDatabaseHas('lead_audits', ['event' => 'followup_added', 'user_id' => $user->id]);
    }

    public function test_callback_notes_and_followup_dates_are_validated(): void
    {
        $lead = $this->lead();
        $lead->forceFill(['created_at' => now()->subDay()])->save();
        $url = route('leads.followups.store', $lead);
        $this->post($url, ['outcome' => 'Another Call', 'followup_at' => now()->subMinute()->toDateTimeString()])->assertSessionHasErrors('next_followup_at');
        $this->post($url, ['outcome' => 'Another Call', 'followup_at' => now()->subHour()->toDateTimeString(), 'next_followup_at' => now()->subMinutes(20)->toDateTimeString()])->assertSessionHasErrors('next_followup_at');
        $this->post($url, ['outcome' => 'Others', 'followup_at' => now()->subMinute()->toDateTimeString()])->assertSessionHasErrors('remarks');
        $this->post($url, ['outcome' => 'RNR', 'followup_at' => now()->addDay()->toDateTimeString()])->assertSessionHasErrors('followup_at');
        $this->post($url, ['outcome' => 'RNR', 'followup_at' => now()->subDays(2)->toDateTimeString()])->assertSessionHasErrors('followup_at');
        $this->post($url, ['outcome' => 'Unknown', 'followup_at' => now()->toDateTimeString(), 'created_by' => 123])->assertSessionHasErrors(['outcome', 'created_by']);
        $this->assertDatabaseCount('lead_followups', 0);
    }

    public function test_pagination_approved_sorting_search_and_combined_filters_work_on_server(): void
    {
        [$user, $employee] = $this->employee();
        $created = now()->subDays(2);
        for ($i = 0; $i < 23; $i++) {
            $lead = $this->lead(['lead_name' => sprintf('Lead %02d', $i), 'mobile_number' => '987654'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), 'assigned_to' => $employee->id, 'location' => 'Madurai']);
            $lead->forceFill(['created_at' => $created])->save();
            $lead->followups()->create(['outcome' => 'Another Call', 'followup_at' => now()->subHour(), 'next_followup_at' => now()->addDay(), 'created_by' => $this->admin->id]);
        }
        $this->lead(['mobile_number' => '7654321098', 'lead_name' => 'Excluded customer', 'location' => 'Chennai']);
        $filters = ['search' => 'Lead', 'location' => 'Madurai', 'assigned_to' => $employee->id, 'status' => 'Active', 'outcome' => 'Another Call', 'next_followup' => now()->addDay()->toDateString(), 'from' => $created->toDateString(), 'to' => now()->toDateString(), 'sort' => 'lead_name', 'direction' => 'asc'];
        $this->get(route('leads.index', $filters))->assertOk()->assertViewHas('leads', fn ($leads) => $leads->total() === 23 && $leads->count() === 20)->assertSeeInOrder(['Lead 00', 'Lead 01'])->assertDontSee('Excluded customer');
        $this->get(route('leads.index', $filters + ['page' => 2]))->assertOk()->assertViewHas('leads', fn ($leads) => $leads->count() === 3)->assertSee('Lead 22');
        $this->get(route('leads.index', ['search' => '9876540001']))->assertOk()->assertViewHas('leads', fn ($leads) => $leads->total() === 1);
        $this->get(route('leads.dashboard'))->assertOk()->assertSee('Callbacks due or overdue');
    }

    public function test_invalid_filters_dates_and_sort_columns_are_rejected(): void
    {
        foreach ([['sort' => 'id desc; DROP TABLE leads'], ['status' => 'Deleted'], ['outcome' => 'Unknown'], ['from' => '2026-10-22', 'to' => '2026-01-01'], ['next_followup' => 'not-a-date'], ['page' => -1]] as $filters) {
            $this->get(route('leads.index', $filters))->assertSessionHasErrors();
        }
        // A standalone upper date bound is allowed.
        $this->get(route('leads.index', ['to' => now()->toDateString()]))->assertOk();
    }

    public function test_exports_are_scoped_audited_and_formula_safe(): void
    {
        $this->lead(['lead_name' => '=HYPERLINK("bad")', 'location' => '+Formula', 'requirement' => '@SUM(A1)', 'source_data' => ['secret' => 'never export raw source']]);
        $this->lead(['lead_name' => 'Excluded lead', 'mobile_number' => '8765432109', 'location' => 'Other']);
        $response = $this->get(route('leads.export', ['location' => '+Formula']))->assertOk()->assertDownload('abj-leads.csv');
        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString("'+Formula", $csv);
        $this->assertStringContainsString("'@SUM", $csv);
        $this->assertStringNotContainsString('Excluded lead', $csv);
        $this->assertStringNotContainsString('never export raw source', $csv);
        $this->assertDatabaseHas('lead_audits', ['event' => 'exported']);
    }
}
