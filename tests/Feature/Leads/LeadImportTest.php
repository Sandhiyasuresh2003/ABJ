<?php

namespace Tests\Feature\Leads;

use App\Models\Lead;
use App\Models\LeadAudit;
use App\Models\User;
use App\Services\Leads\LeadWorkbook;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class LeadImportTest extends LeadTestCase
{
    public function test_valid_xlsx_requires_preview_and_confirmation_and_records_summary(): void
    {
        $import = $this->upload($this->xlsx([['Lead Name', 'Mobile Number', 'Location', 'Requirement'], ['Arun', '+91 98765 43210', 'Chennai', 'Plot']]));
        $path = $import->path;
        Storage::disk('local')->assertExists($path);
        $this->get(route('leads.imports.show', $import))->assertOk()->assertSee('Map lead columns');
        $this->assertDatabaseCount('leads', 0);
        $this->preview($import);
        $this->get(route('leads.imports.show', $import))->assertOk()->assertSee('Arun')->assertSee('Confirm import');
        $this->assertDatabaseCount('leads', 0);
        $import = $this->confirm($import);
        $this->assertSame('completed', $import->status);
        $this->assertSame(1, $import->imported_rows);
        $this->assertNull($import->payload);
        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseHas('leads', ['lead_name' => 'Arun', 'normalized_mobile' => '9876543210', 'status' => 'Active']);
        $this->assertDatabaseHas('lead_audits', ['event' => 'imported', 'user_id' => $this->admin->id]);
        $this->get(route('leads.imports.show', $import))->assertOk()->assertSee('Import results');
        $this->get(route('leads.imports.index'))->assertOk()->assertSee('leads.xlsx');
        $this->confirm($import);
        $this->assertDatabaseCount('leads', 1);
    }

    public function test_owner_can_import_leads_without_assignment_permission(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner);
        $this->get(route('leads.imports.create'))->assertOk();

        $import = $this->upload($this->xlsx([['Lead Name', 'Mobile Number', 'Location', 'Requirement'], ['Owner import', '9876543210', 'Chennai', 'Plot']]));
        $this->preview($import);
        $result = $this->confirm($import);

        $this->assertSame(1, $result->imported_rows);
        $this->assertDatabaseHas('leads', ['lead_name' => 'Owner import', 'created_by' => $owner->id, 'assigned_to' => null]);
    }

    public function test_csv_duplicate_and_invalid_rows_are_identified_before_any_write(): void
    {
        $this->lead();
        $import = $this->upload($this->csv("Lead Name,Mobile Number,Location,Requirement\nExisting,+91 9876543210,Chennai,Plot\nFirst,8765432109,Madurai,Land\nRepeated,08765432109,Madurai,Land\nInvalid,123,Madurai,House\n,7654321098,Madurai,House\n"));
        $import = $this->preview($import);
        $this->assertSame(5, $import->total_rows);
        $this->assertSame(2, $import->duplicate_rows);
        $this->assertSame(2, $import->failed_rows);
        $this->assertDatabaseCount('leads', 1);
        $this->assertStringNotContainsString('8765432109', $import->getRawOriginal('payload'));
        $result = $this->confirm($import);
        $this->assertSame(1, $result->imported_rows);
        $this->assertSame(2, $result->duplicate_rows);
        $this->assertSame(2, $result->failed_rows);
    }

    public function test_existing_sales_workbook_headers_and_selected_sheet_can_be_mapped(): void
    {
        [$user, $employee] = $this->employee();
        $headers = ['DATE', 'CUSTOMER NAME', 'MOBILE NO', 'TELECALLER', 'STATUS', 'REMARKS'];
        $import = $this->upload($this->xlsx([$headers, ['2026-10-01', 'First sheet', '9876543210', $employee->employee_code, 'RNR', 'Original note']], [$headers, ['2026-10-02', 'Second sheet', '8765432109', $employee->email, 'Not Interested', 'Retain this note']]));
        $this->get(route('leads.imports.show', ['import' => $import, 'sheet' => 1]))->assertOk()->assertSee('Other sales')->assertSee('CUSTOMER NAME');
        $this->preview($import, ['sheet' => 1, 'mapping' => ['lead_name' => 1, 'mobile_number' => 2, 'location' => null, 'requirement' => null, 'assigned_to' => 3], 'defaults' => ['location' => 'Madurai', 'requirement' => 'Plot']]);
        $this->confirm($import);
        $lead = Lead::firstOrFail();
        $this->assertSame('Second sheet', $lead->lead_name);
        $this->assertSame($employee->id, $lead->assigned_to);
        $this->assertSame('Active', $lead->status);
        $this->assertSame('Retain this note', $lead->source_data['REMARKS']);
        $this->assertDatabaseCount('lead_followups', 0);
        $this->assertDatabaseCount('lead_assignments', 1);
    }

    public function test_location_is_optional_but_requirement_needs_an_explicit_default(): void
    {
        $import = $this->upload($this->csv("CUSTOMER NAME,MOBILE NO\nCustomer,9876543210\n"));
        $this->post(route('leads.imports.preview', $import), ['sheet' => 0, 'mapping' => ['lead_name' => 0, 'mobile_number' => 1]])->assertSessionHasErrors('defaults.requirement');
        $this->assertSame('uploaded', $import->refresh()->status);
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_location_can_be_omitted_during_import(): void
    {
        $import = $this->upload($this->xlsx([['Lead Name', 'Mobile Number', 'Requirement'], ['No location', '9876543210', 'Plot']]));
        $import = $this->preview($import, ['mapping' => ['location' => null, 'requirement' => 2]]);

        $this->assertSame('valid', $import->payload[0]['state']);
        $result = $this->confirm($import);

        $this->assertSame(1, $result->imported_rows);
        $this->assertDatabaseHas('leads', ['lead_name' => 'No location', 'location' => '']);
    }

    public function test_invalid_file_extensions_mime_types_and_oversized_uploads_are_rejected(): void
    {
        foreach (['leads.php', 'leads.xls', 'leads.xlsx'] as $name) {
            $this->post(route('leads.imports.store'), ['file' => UploadedFile::fake()->createWithContent($name, '<?php echo 1;')])->assertSessionHasErrors('file');
        }
        $this->post(route('leads.imports.store'), ['file' => UploadedFile::fake()->create('large.csv', config('leads.max_upload_kb') + 1, 'text/csv')])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('lead_imports', 0);
    }

    public function test_excessive_csv_rows_are_rejected(): void
    {
        config(['leads.max_rows' => 2]);
        $this->post(route('leads.imports.store'), ['file' => $this->csv("Name,Mobile\nA,9876543210\nB,8765432109\nC,7654321098\n")])->assertSessionHasErrors('file');
    }

    public function test_excessive_xlsx_rows_and_out_of_range_column_mapping_are_rejected(): void
    {
        config(['leads.max_rows' => 1]);
        $import = $this->upload($this->xlsx([['Name', 'Mobile'], ['A', '9876543210'], ['B', '8765432109']]));
        $this->get(route('leads.imports.show', $import))->assertSessionHasErrors('file');
        config(['leads.max_rows' => 5000]);
        $this->post(route('leads.imports.preview', $import), ['sheet' => 0, 'mapping' => ['lead_name' => 99, 'mobile_number' => 1]])->assertSessionHasErrors('mapping.lead_name');
    }

    public function test_formula_cells_are_not_calculated_and_are_invalid(): void
    {
        $import = $this->upload($this->xlsx([['Lead Name', 'Mobile Number', 'Location', 'Requirement'], ['=HYPERLINK("https://invalid.example/","Name")', '9876543210', 'Madurai', 'Plot']]));
        $this->preview($import);
        $this->assertSame('invalid', $import->payload[0]['state']);
        $this->confirm($import);
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_assignment_is_authorized_validated_and_rechecked_at_confirmation(): void
    {
        [$user, $employee] = $this->employee();
        $import = $this->upload($this->csv("Lead Name,Mobile Number,Location,Requirement,Assigned Employee\nA,9876543210,Madurai,Plot,{$employee->employee_code}\nB,8765432109,Madurai,Plot,Unknown\n"));
        $this->preview($import, ['mapping' => ['assigned_to' => 4]]);
        $this->assertSame(1, $import->failed_rows);
        $employee->update(['employment_status' => 'Inactive']);
        $result = $this->confirm($import);
        $this->assertSame(2, $result->failed_rows);
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_two_previews_for_the_same_mobile_cannot_insert_duplicates(): void
    {
        $csv = "Lead Name,Mobile Number,Location,Requirement\nA,9876543210,Madurai,Plot\n";
        $first = $this->preview($this->upload($this->csv($csv)));
        $second = $this->preview($this->upload($this->csv($csv)));
        $this->assertSame('valid', $first->payload[0]['state']);
        $this->assertSame('valid', $second->payload[0]['state']);
        $this->assertSame(1, $this->confirm($first)->imported_rows);
        $this->assertSame(1, $this->confirm($second)->duplicate_rows);
        $this->assertDatabaseCount('leads', 1);
    }

    public function test_an_unexpected_failure_rolls_back_the_entire_import_and_allows_retry(): void
    {
        $import = $this->preview($this->upload($this->csv("Lead Name,Mobile Number,Location,Requirement\nA,9876543210,Madurai,Plot\nB,8765432109,Madurai,Plot\n")));
        LeadAudit::creating(function (LeadAudit $audit) {
            if ($audit->event === 'imported') {
                throw new \RuntimeException('Simulated storage failure');
            }
        });
        try {
            $this->post(route('leads.imports.confirm', $import), ['confirm' => 1])->assertSessionHasErrors('file');
            $this->assertDatabaseCount('leads', 0);
            $this->assertSame('failed', $import->refresh()->status);
        } finally {
            LeadAudit::flushEventListeners();
        }
        $this->assertSame(2, $this->confirm($import)->imported_rows);
    }

    public function test_import_ownership_expiry_and_retention_are_enforced(): void
    {
        $import = $this->preview($this->upload($this->csv("Lead Name,Mobile Number,Location,Requirement\nA,9876543210,Madurai,Plot\n")));
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get(route('leads.imports.show', $import))->assertForbidden();
        $this->post(route('leads.imports.confirm', $import), ['confirm' => 1])->assertForbidden();
        $this->actingAs($this->admin);
        $import->update(['expires_at' => now()->subMinute()]);
        $this->post(route('leads.imports.confirm', $import), ['confirm' => 1])->assertStatus(422);
        $path = $import->path;
        $this->artisan('leads:prune-imports')->assertSuccessful();
        $this->assertNull($import->refresh()->payload);
        $this->assertSame('expired', $import->status);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_template_download_is_a_valid_xlsx(): void
    {
        $response = $this->get(route('leads.imports.template'))->assertOk()->assertDownload('abj-leads-template.xlsx');
        $content = $response->streamedContent();
        $this->assertStringStartsWith('PK', $content);

        $path = tempnam(sys_get_temp_dir(), 'leads-template-');
        try {
            file_put_contents($path, $content);
            $book = IOFactory::load($path);
            try {
                $this->assertSame(LeadWorkbook::HEADERS, $book->getActiveSheet()->rangeToArray('A1:E1', null, true, false)[0]);
            } finally {
                $book->disconnectWorksheets();
            }
        } finally {
            unlink($path);
        }
    }
}
