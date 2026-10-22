<?php

use App\Models\LeadImport;
use App\Models\User;
use App\Services\Leads\LeadImporter;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

// Test-only worker: never uses the application's normal database configuration.
require __DIR__.'/../../vendor/autoload.php';

$mysql = ($argv[1] ?? '') === '--mysql';
$database = $argv[$mysql ? 2 : 1] ?? '';
if ($mysql ? ! preg_match('/^abj_leads_test_[a-f0-9]{20}$/D', $database) : (! is_file($database) || ! str_starts_with(basename($database), 'leads-race-'))) {
    exit(2);
}
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if ($mysql) {
    config(['database.default' => 'mysql', 'database.connections.mysql.database' => $database, 'database.connections.mysql.url' => null]);
    DB::purge('mysql');
} else {
    config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $database, 'database.connections.sqlite.busy_timeout' => 10000, 'database.connections.sqlite.transaction_mode' => 'IMMEDIATE']);
    DB::purge('sqlite');
}
try {
    (new LeadImporter)->confirm(User::findOrFail((int) $argv[$mysql ? 3 : 2]), LeadImport::findOrFail((int) $argv[$mysql ? 4 : 3]));
    echo 'completed';
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception));
    exit(1);
}
