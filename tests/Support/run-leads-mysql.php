<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

// Integration runner creates and drops ONLY its own randomly named test database.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$name = 'abj_leads_test_'.bin2hex(random_bytes(10));
$connection = config('database.connections.mysql');
$connection['database'] = null;
$connection['url'] = null;
config(['database.connections.leads_test_server' => $connection]);
$server = DB::connection('leads_test_server');
$created = false;
try {
    $server->statement('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $test = new Process([PHP_BINARY, 'artisan', 'test', '--compact', 'tests/Feature/Leads'], base_path(), [
        'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => $name, 'DB_URL' => false,
        'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'LEADS_EXPECT_MYSQL' => '1',
    ], timeout: 120);
    $test->run(fn ($type, $buffer) => print ($buffer));
    $exitCode = $test->getExitCode();
} catch (Throwable $exception) {
    fwrite(STDERR, 'Isolated MySQL test setup failed ('.get_class($exception).'). Check local MySQL test privileges.'.PHP_EOL);
    $exitCode = 1;
} finally {
    if ($created && preg_match('/^abj_leads_test_[a-f0-9]{20}$/D', $name)) {
        $server->statement('DROP DATABASE `'.$name.'`');
    }
    $server->disconnect();
}
exit($exitCode);
