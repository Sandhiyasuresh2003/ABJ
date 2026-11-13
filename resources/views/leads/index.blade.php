@extends('layouts.app')
@section('title', auth()->user()->role === 'employee' ? 'My assigned leads' : 'All leads')
@section('heading', auth()->user()->role === 'employee' ? 'My assigned leads' : 'All leads')
@section('breadcrumb','Leads')
@section('content')
@include('leads.nav')
<form method="GET" action="{{ route('leads.index') }}" class="rounded-2xl border border-white/70 bg-white/65 p-5 shadow-xl shadow-slate-900/10 backdrop-blur-md">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @if(in_array('mobile', config('leads.permissions.'.auth()->user()->role, []), true))
        <div><label for="mobile" class="mb-1 block text-sm font-semibold">Mobile number</label><input id="mobile" class="field" type="search" name="mobile" maxlength="25" inputmode="tel" value="{{ $filters['mobile'] ?? '' }}"></div>
        @endif
        <div><label for="project-category" class="mb-1 block text-sm font-semibold">Project category</label><select id="project-category" class="field" name="project_category"><option value="">All categories</option>@foreach(App\Services\Leads\LeadData::PROJECT_CATEGORIES as $category)<option value="{{ $category }}" @selected(($filters['project_category'] ?? '')===$category)>{{ $category }}</option>@endforeach</select></div>
        <div><label for="employee" class="mb-1 block text-sm font-semibold">Assigned employee</label><select id="employee" class="field" name="assigned_to"><option value="">All accessible employees</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" @selected(($filters['assigned_to']??'')==$employee->id)>{{ $employee->employee_code }} — {{ $employee->name }}</option>@endforeach</select></div>
        <div><label for="text" class="mb-1 block text-sm font-semibold">Follow-up outcome or requirement text</label><input id="text" class="field" type="search" name="text" maxlength="1000" value="{{ $filters['text'] ?? '' }}"></div>
    </div>
    <div class="mt-4 flex flex-wrap gap-3"><button class="btn btn-primary">Apply filters</button><a class="btn btn-light" href="{{ route('leads.index') }}">Reset</a>@can('export',App\Models\Lead::class)<a class="btn btn-light" href="{{ route('leads.export',$filters) }}" onclick="return confirm('Download the customer details matching these filters?')">Export filtered CSV</a>@endcan</div>
</form>
<div class="overflow-hidden rounded-2xl border border-white/70 bg-white/70 shadow-xl shadow-slate-900/10 backdrop-blur-md">
    <p class="border-b border-white/60 bg-white/30 px-5 py-3 text-sm font-medium text-slate-700">{{ $leads->total() }} matching leads</p>
    <div class="max-h-[60vh] overflow-auto"><table class="w-full text-left text-sm"><thead class="sticky top-0 z-10 bg-emerald-950/90 text-xs uppercase tracking-wide text-emerald-50"><tr>@foreach(['Lead name','Mobile number','Assigned employee','Status','Actions'] as $heading)<th class="whitespace-nowrap px-4 py-3">{{ $heading }}</th>@endforeach</tr></thead><tbody class="divide-y divide-white/70">
    @forelse($leads as $lead)
    <tr class="align-top bg-white/45 transition hover:bg-white/75">
        <td class="px-4 py-4"><a class="font-semibold text-navy-950 hover:underline" href="{{ route('leads.show',$lead) }}">{{ $lead->lead_name ?: 'Name not provided' }}</a><a class="ml-3 text-xs font-semibold text-emerald-800 underline decoration-emerald-700/40 underline-offset-2 hover:text-emerald-950" href="{{ route('leads.followups.history',$lead) }}">Show follow-ups</a></td>
        <td class="whitespace-nowrap px-4 py-4">{{ $lead->displayMobile(auth()->user()) }}</td>
        <td class="px-4 py-4">{{ $lead->employee?->name ?? 'Unassigned' }}</td>
        <td class="px-4 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $lead->status === 'Active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">{{ $lead->status }}</span></td>
        <td class="px-4 py-4"><div class="flex flex-wrap gap-2">
            @can('update',$lead)<a class="text-xs font-semibold text-emerald-800 underline underline-offset-2" href="{{ route('leads.edit',$lead) }}">Edit</a>@endcan
            @can('followup',$lead)<a class="text-xs font-semibold text-emerald-800 underline underline-offset-2" href="{{ route('leads.followup',$lead) }}">Add follow-up</a>@endcan
            @can('status',$lead)<a class="text-xs font-semibold text-emerald-800 underline underline-offset-2" href="{{ route('leads.show',$lead) }}#lead-status">Edit status</a>@endcan
        </div></td>
    </tr>
    @empty<tr><td colspan="5" class="bg-white/45 px-5 py-12 text-center text-slate-600">No leads match these filters. Clear filters or add a lead if you have permission.</td></tr>@endforelse
    </tbody></table></div><div class="border-t border-slate-200 p-5">{{ $leads->links() }}</div>
</div>
@endsection
