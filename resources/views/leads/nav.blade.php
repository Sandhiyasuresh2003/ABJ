<nav class="flex flex-wrap gap-2" aria-label="Lead management">
    <a class="btn btn-light" href="{{ route('leads.dashboard') }}">Leads dashboard</a>
    <a class="btn btn-light" href="{{ route('leads.index') }}">All leads</a>
    @can('create', App\Models\Lead::class)<a class="btn btn-primary" href="{{ route('leads.create') }}">New lead</a>@endcan
    @can('import', App\Models\Lead::class)<a class="btn btn-light" href="{{ route('leads.imports.create') }}">Import leads</a>@endcan
    @can('history', App\Models\Lead::class)<a class="btn btn-light" href="{{ route('leads.imports.index') }}">Import history</a>@endcan
</nav>
<p id="lead-loading" class="hidden rounded-lg bg-green-50 p-3 text-sm text-green-800" role="status" aria-live="polite">Processing your request…</p>
@once
<script>document.addEventListener('submit',function(event){if(!event.defaultPrevented){document.getElementById('lead-loading')?.classList.remove('hidden');}});</script>
@endonce
