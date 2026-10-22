<?php

namespace Tests\Feature\Leads;

use App\Models\Lead;
use App\Models\LeadAudit;
use App\Models\LeadImport;
use App\Models\User;
use App\Services\Leads\LeadImporter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class LeadConcurrencyTest extends TestCase
{
    public function test_simultaneous_import_processes_cannot_insert_the_same_mobile(): void
    {
        if (getenv('LEADS_EXPECT_MYSQL') === '1') {
            $this->runMySqlRace();

            return;
        }
        $database = sys_get_temp_dir().DIRECTORY_SEPARATOR.'leads-race-'.bin2hex(random_bytes(12)).'.sqlite';
        touch($database);
        $workers = [];
        $original = config('database.connections.sqlite');
        try {
            config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $database, 'database.connections.sqlite.busy_timeout' => 10000, 'database.connections.sqlite.transaction_mode' => 'IMMEDIATE']);
            DB::purge('sqlite');
            // Only the isolated, randomly named temporary database is migrated.
            Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]);
            $admin = User::factory()->create(['role' => 'admin']);
            $payload = (new LeadImporter)->preview($admin, [['Lead Name', 'Mobile Number', 'Location', 'Requirement'], ['Customer', '9876543210', 'Madurai', 'Plot']], ['lead_name' => 0, 'mobile_number' => 1, 'location' => 2, 'requirement' => 3], []);
            $imports = [];
            for ($i = 0; $i < 2; $i++) {
                $imports[] = LeadImport::create(['uploaded_by' => $admin->id, 'original_filename' => 'race.csv', 'status' => 'preview', 'payload' => $payload, 'total_rows' => 1, 'expires_at' => now()->addHour()]);
            }
            $workers = array_map(fn ($import) => new Process([PHP_BINARY, base_path('tests/Support/confirm-lead-import.php'), $database, (string) $admin->id, (string) $import->id], base_path(), ['APP_ENV' => 'testing', 'APP_KEY' => config('app.key'), 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array'], timeout: 30), $imports);
            foreach ($workers as $worker) {
                $worker->start();
            }
            foreach ($workers as $worker) {
                $worker->wait();
                $this->assertTrue($worker->isSuccessful(), 'Exit '.$worker->getExitCode().': '.$worker->getErrorOutput().$worker->getOutput());
            }
            $this->assertSame(1, Lead::count());
            $this->assertSame(1, (int) LeadImport::sum('imported_rows'));
            $this->assertSame(1, (int) LeadImport::sum('duplicate_rows'));
            $this->assertSame(2, LeadImport::where('status', 'completed')->count());
        } finally {
            foreach ($workers as $worker) {
                if ($worker->isRunning()) {
                    $worker->stop();
                }
            }
            DB::disconnect('sqlite');
            config(['database.connections.sqlite' => $original]);
            DB::purge('sqlite');
            foreach ([$database, $database.'-wal', $database.'-shm'] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    private function runMySqlRace(): void
    {
        $database = config('database.connections.mysql.database');
        $this->assertMatchesRegularExpression('/^abj_leads_test_[a-f0-9]{20}$/D', $database);
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $admin = User::factory()->create(['role' => 'admin']);
        $workers = [];
        try {
            $payload = (new LeadImporter)->preview($admin, [['Name', 'Mobile', 'Location', 'Requirement'], ['Concurrent MySQL lead', '9999999999', 'Madurai', 'Plot']], ['lead_name' => 0, 'mobile_number' => 1, 'location' => 2, 'requirement' => 3], []);
            for ($i = 0; $i < 2; $i++) {
                $import = LeadImport::create(['uploaded_by' => $admin->id, 'original_filename' => 'race.csv', 'status' => 'preview', 'payload' => $payload, 'total_rows' => 1, 'expires_at' => now()->addHour()]);
                $workers[] = new Process([PHP_BINARY, base_path('tests/Support/confirm-lead-import.php'), '--mysql', $database, (string) $admin->id, (string) $import->id], base_path(), ['APP_ENV' => 'testing', 'APP_KEY' => config('app.key'), 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array'], timeout: 30);
            }
            foreach ($workers as $worker) {
                $worker->start();
            }
            foreach ($workers as $worker) {
                $worker->wait();
                $this->assertTrue($worker->isSuccessful(), $worker->getErrorOutput().$worker->getOutput());
            }
            $this->assertSame(1, Lead::where('created_by', $admin->id)->count());
            $this->assertSame(1, (int) LeadImport::where('uploaded_by', $admin->id)->sum('imported_rows'));
            $this->assertSame(1, (int) LeadImport::where('uploaded_by', $admin->id)->sum('duplicate_rows'));
            $this->assertSame(2, LeadImport::where('uploaded_by', $admin->id)->where('status', 'completed')->count());
        } finally {
            foreach ($workers as $worker) {
                if ($worker->isRunning()) {
                    $worker->stop();
                }
            }
            LeadAudit::where('user_id', $admin->id)->delete();
            LeadImport::where('uploaded_by', $admin->id)->delete();
            Lead::where('created_by', $admin->id)->delete();
            $admin->delete();
        }
    }
}
