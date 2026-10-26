<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use App\Models\LeadImport;

Artisan::command('leads:prune-imports', function () {
    $count = 0;
    LeadImport::where('expires_at', '<=', now())->where(function ($q) {
        $q->whereNotNull('path')->orWhereNotNull('payload')->orWhereIn('status', ['uploaded', 'preview', 'failed']);
    })->eachById(function (LeadImport $import) use (&$count) {
        \Illuminate\Support\Facades\DB::transaction(function () use ($import, &$count) {
            $locked = LeadImport::whereKey($import->id)->lockForUpdate()->first();
            if (! $locked || $locked->expires_at->isFuture()) { return; }
            if ($locked->path && ! Storage::disk('local')->delete($locked->path)) { return; }
            $locked->update(['path' => null, 'payload' => null, 'status' => $locked->status === 'completed' ? 'completed' : 'expired']);
            $count++;
        });
    });
    foreach (Storage::disk('local')->files('lead-imports') as $path) {
        if (Storage::disk('local')->lastModified($path) < now()->subHours(config('leads.preview_hours'))->timestamp && ! LeadImport::where('path', $path)->exists()) {
            Storage::disk('local')->delete($path);
        }
    }
    $this->info("Pruned {$count} expired import payloads; summary history retained.");
})->purpose('Remove expired private lead uploads and preview data');

Schedule::command('leads:prune-imports')->hourly()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
