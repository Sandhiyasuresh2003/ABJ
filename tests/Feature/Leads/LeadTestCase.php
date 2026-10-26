<?php

namespace Tests\Feature\Leads;

use App\Models\Employee;
use App\Models\Lead;
use App\Models\LeadImport;
use App\Models\User;
use App\Services\Leads\LeadActions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

abstract class LeadTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('LEADS_EXPECT_MYSQL') === '1') {
            $this->assertSame('mysql', DB::connection()->getDriverName());
        }
        Storage::fake('local');
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->admin);
    }

    protected function employee(string $name = 'Sales employee'): array
    {
        $user = User::factory()->create(['role' => 'employee', 'name' => $name]);
        $employee = Employee::create(['employee_code' => 'EMP'.$user->id, 'name' => $name, 'email' => $user->email, 'department' => 'Sales', 'designation' => 'Executive', 'joining_date' => '2026-01-01', 'employment_status' => 'Active']);

        return [$user, $employee];
    }

    protected function lead(array $data = []): Lead
    {
        return (new LeadActions)->create($this->admin, array_merge(['lead_name' => 'Customer', 'mobile_number' => '9876543210', 'location' => 'Chennai', 'requirement' => 'House'], $data));
    }

    protected function csv(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('leads.csv', $content);
    }

    protected function xlsx(array $rows, ?array $secondSheet = null): UploadedFile
    {
        $book = new Spreadsheet;
        $book->getActiveSheet()->setTitle('Sales')->fromArray($rows);
        if ($secondSheet) {
            $book->createSheet()->setTitle('Other sales')->fromArray($secondSheet);
        }
        $path = tempnam(sys_get_temp_dir(), 'leads-test-');
        try {
            (new Xlsx($book))->setPreCalculateFormulas(false)->save($path);

            return UploadedFile::fake()->createWithContent('leads.xlsx', file_get_contents($path));
        } finally {
            $book->disconnectWorksheets();
            unlink($path);
        }
    }

    protected function upload(UploadedFile $file): LeadImport
    {
        $this->post(route('leads.imports.store'), ['file' => $file])->assertSessionHasNoErrors()->assertRedirect();

        return LeadImport::latest('id')->firstOrFail();
    }

    protected function preview(LeadImport $import, array $extra = []): LeadImport
    {
        $this->post(route('leads.imports.preview', $import), array_replace_recursive(['sheet' => 0, 'mapping' => ['lead_name' => 0, 'mobile_number' => 1, 'location' => 2, 'requirement' => 3, 'assigned_to' => null]], $extra))->assertSessionHasNoErrors()->assertRedirect();

        return $import->refresh();
    }

    protected function confirm(LeadImport $import): LeadImport
    {
        $this->post(route('leads.imports.confirm', $import), ['confirm' => 1])->assertSessionHasNoErrors()->assertRedirect();

        return $import->refresh();
    }
}
