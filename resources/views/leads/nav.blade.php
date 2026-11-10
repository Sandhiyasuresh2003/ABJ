@php
    $currentRoute = request()->route()?->getName();
    $activeTab = match (true) {
        $currentRoute === 'leads.dashboard' => 'dashboard',
        $currentRoute === 'leads.index' || $currentRoute === 'leads.show' || $currentRoute === 'leads.edit' || $currentRoute === 'leads.assignment' || $currentRoute === 'leads.followup' => 'all',
        $currentRoute === 'leads.create' || $currentRoute === 'leads.store' || $currentRoute === 'leads.update' => 'new',
        $currentRoute === 'leads.imports.create' || $currentRoute === 'leads.imports.show' || $currentRoute === 'leads.imports.preview' => 'imports',
        default => null,
    };
@endphp

<nav class="flex flex-wrap gap-2" aria-label="Lead management">
    <a class="btn {{ $activeTab === 'dashboard' ? 'btn-primary' : 'btn-light' }}" href="{{ route('leads.dashboard') }}">Leads dashboard</a>
    <a class="btn {{ $activeTab === 'all' ? 'btn-primary' : 'btn-light' }}" href="{{ route('leads.index') }}">All leads</a>
    @can('create', App\Models\Lead::class)<a class="btn {{ $activeTab === 'new' ? 'btn-primary' : 'btn-light' }}" href="{{ route('leads.create') }}">New lead</a>@endcan
    @can('import', App\Models\Lead::class)<a class="btn {{ $activeTab === 'imports' ? 'btn-primary' : 'btn-light' }}" href="{{ route('leads.imports.create') }}">Import leads</a>@endcan
</nav>
<p id="lead-loading" class="hidden rounded-lg bg-green-50 p-3 text-sm text-green-800" role="status" aria-live="polite">Processing your request…</p>
@once
<script>document.addEventListener('submit',function(event){if(!event.defaultPrevented){document.getElementById('lead-loading')?.classList.remove('hidden');}});</script>
@endonce
