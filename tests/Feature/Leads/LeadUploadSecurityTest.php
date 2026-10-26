<?php

namespace Tests\Feature\Leads;

use App\Services\Leads\LeadImporter;
use Illuminate\Http\UploadedFile;
use ZipArchive;

class LeadUploadSecurityTest extends LeadTestCase
{
    private function maliciousWorkbook(string $entry, string $content): UploadedFile
    {
        $file = $this->xlsx([['Lead Name', 'Mobile Number', 'Location', 'Requirement'], ['A', '9876543210', 'Madurai', 'Plot']]);
        $path = tempnam(sys_get_temp_dir(), 'xlsx-security-');
        try {
            file_put_contents($path, file_get_contents($file->getPathname()));
            $zip = new ZipArchive;
            $zip->open($path);
            $zip->addFromString($entry, $content);
            $zip->close();

            return UploadedFile::fake()->createWithContent('leads.xlsx', file_get_contents($path));
        } finally {
            unlink($path);
        }
    }

    public function test_macros_embeddings_external_workbooks_and_xml_entities_are_rejected(): void
    {
        foreach ([['xl/vbaProject.bin', 'macro'], ['xl/embeddings/object.bin', 'embedded'], ['xl/externalLinks/externalLink1.xml', '<externalLink/>'], ['xl/evil.xml', '<!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///secret">]><foo>&xxe;</foo>']] as [$entry, $content]) {
            $this->post(route('leads.imports.store'), ['file' => $this->maliciousWorkbook($entry, $content)])->assertSessionHasErrors('file');
        }
        $this->assertDatabaseCount('lead_imports', 0);
    }

    public function test_expanded_archive_size_is_bounded(): void
    {
        config(['leads.max_uncompressed_bytes' => 1024]);
        $this->post(route('leads.imports.store'), ['file' => $this->xlsx([['Name', 'Mobile'], ['A', '9876543210']])])->assertSessionHasErrors('file');
    }

    public function test_csv_formula_like_and_malformed_rows_are_invalid(): void
    {
        $import = $this->preview($this->upload($this->csv("Lead Name,Mobile Number,Location,Requirement\n=1+1,9876543210,Madurai,Plot\nA,8765432109,Madurai,Plot,Unexpected extra cell\n")));
        $this->assertSame(2, $import->failed_rows);
        $this->confirm($import);
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_import_permission_does_not_grant_assignment_permission(): void
    {
        [$user, $employee] = $this->employee();
        config(['leads.permissions.employee' => ['import']]);
        $rows = (new LeadImporter)->preview($user, [['Name', 'Mobile', 'Location', 'Requirement', 'Employee'], ['A', '9876543210', 'Madurai', 'Plot', $employee->employee_code]], ['lead_name' => 0, 'mobile_number' => 1, 'location' => 2, 'requirement' => 3, 'assigned_to' => 4], []);
        $this->assertSame('invalid', $rows[0]['state']);
        $this->assertContains('You cannot assign employees during import.', $rows[0]['errors']);
    }

    public function test_import_requests_are_rate_limited(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('leads.imports.store'), [])->assertSessionHasErrors('file');
        }
        $this->post(route('leads.imports.store'), [])->assertStatus(429);
    }

    public function test_confirm_does_not_accept_tampered_preview_rows_or_assignments(): void
    {
        $import = $this->preview($this->upload($this->csv("Lead Name,Mobile Number,Location,Requirement\nA,9876543210,Madurai,Plot\n")));
        $this->post(route('leads.imports.confirm', $import), ['confirm' => 1, 'payload' => [['lead_name' => 'Injected', 'assigned_to' => 999]], 'assigned_to' => 999])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('leads', ['lead_name' => 'A', 'assigned_to' => null]);
        $this->assertDatabaseMissing('leads', ['lead_name' => 'Injected']);
    }

    public function test_employee_with_lead_history_cannot_be_physically_deleted(): void
    {
        [$user, $employee] = $this->employee();
        $this->lead(['assigned_to' => $employee->id]);
        $this->delete(route('employees.destroy', $employee))->assertSessionHasErrors('employee');
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }
}
