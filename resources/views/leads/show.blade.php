@extends('layouts.app')
@section('title','Lead details')
@section('heading','Lead details')
@section('breadcrumb','Leads')
@section('content')
@include('leads.nav')
<article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft">
    <div class="flex flex-wrap justify-between gap-4"><div><h2 class="text-xl font-bold">{{ $lead->lead_name }}</h2><p class="mt-1 text-sm text-slate-500">Lead #{{ $lead->id }} · Created {{ $lead->created_at->format('d M Y H:i') }} by {{ $lead->creator->name }}</p></div><div class="flex flex-wrap gap-2">@can('update',$lead)<a class="btn btn-light" href="{{ route('leads.edit',$lead) }}">Edit</a>@endcan @can('assign',$lead)<a class="btn btn-light" href="{{ route('leads.assignment',$lead) }}">Assign</a>@endcan @can('followup',$lead)<a class="btn btn-primary" href="{{ route('leads.followup',$lead) }}">Add follow-up</a>@endcan</div></div>
    <dl class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">@foreach(['Mobile'=>$lead->displayMobile(auth()->user()),'Project category'=>$lead->project_category,'Location'=>$lead->location,'Requirement'=>$lead->requirement,'Assigned employee'=>$lead->employee?->name??'Unassigned','Status'=>$lead->status,'Next follow-up'=>$lead->lastFollowup?->next_followup_at?->format('d M Y H:i')??'Not scheduled'] as $label=>$value)<div><dt class="text-xs font-semibold uppercase text-slate-400">{{ $label }}</dt><dd class="mt-1 whitespace-pre-wrap break-words text-sm">{{ $value }}</dd></div>@endforeach</dl>
    <div class="mt-6 grid gap-6 border-t border-slate-100 pt-5 md:grid-cols-2">
        <div>
            <h2 class="font-bold">Booking</h2>
            @if($lead->booked_at)
                <p class="mt-2 text-sm text-emerald-800">Booked on {{ $lead->booked_at->format('d M Y') }} by {{ $lead->bookedBy?->name ?? 'Unknown user' }}. This record cannot be changed here.</p>
            @else
                @can('book', $lead)
                <form method="POST" action="{{ route('leads.book', $lead) }}" class="mt-3 space-y-3">
                    @csrf
                    <div><label for="booked-at" class="mb-1 block text-sm font-semibold">Booking date *</label><input id="booked-at" class="field" type="date" name="booked_at" min="{{ $lead->created_at->toDateString() }}" max="{{ today()->toDateString() }}" value="{{ old('booked_at', today()->toDateString()) }}" required></div>
                    <button class="btn btn-primary">Mark as booked</button>
                </form>
                @else
                <p class="mt-2 text-sm text-slate-500">Not booked.</p>
                @endcan
            @endif
        </div>
        <div class="rounded-xl bg-slate-50 p-4">
            <p class="text-sm font-bold">Site visits</p>
            @can('siteVisit', $lead)
            <form method="POST" action="{{ route('leads.site-visits.store', $lead) }}" class="mt-3 space-y-3">
                @csrf
                <div><label for="site-visit-date" class="mb-1 block text-sm font-semibold">Completed visit date *</label><input id="site-visit-date" class="field" type="date" name="visit_date" min="{{ $lead->created_at->toDateString() }}" max="{{ today()->toDateString() }}" value="{{ old('visit_date', today()->toDateString()) }}" required></div>
                <div><label for="site-visit-details" class="mb-1 block text-sm font-semibold">Details</label><textarea id="site-visit-details" class="field" rows="2" name="details" maxlength="2000">{{ old('details') }}</textarea></div>
                <button class="btn btn-light">Record completed site visit</button>
                <p class="text-xs text-slate-500">Site visit records are permanent and cannot be edited or deleted.</p>
            </form>
            @endcan
        </div>
    </div>
    @can('status',$lead)
    <form id="lead-status" method="POST" action="{{ route('leads.status',$lead) }}" class="mt-6 scroll-mt-24 flex flex-wrap items-end gap-3 border-t border-slate-100 pt-5" onsubmit="return confirm('Change this lead’s status? Its records will remain in history.')">@csrf @method('PATCH')<div><label for="new-status" class="mb-1 block text-sm font-semibold">Change status</label><select id="new-status" class="field" name="status">@foreach(['Active','Inactive'] as $status)<option @selected($lead->status===$status)>{{ $status }}</option>@endforeach</select></div><div class="min-w-48 flex-1"><label for="status-reason" class="mb-1 block text-sm font-semibold">Reason *</label><input id="status-reason" class="field" name="reason" maxlength="1000" value="{{ old('reason') }}" required></div><button class="btn btn-light">Save status</button></form>
    @endcan
</article>
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft">
    <h2 class="mb-1 text-lg font-bold">Completed site visit history</h2>
    <p class="mb-4 text-xs text-slate-500">Permanent entries · newest first.</p>
    <div class="space-y-3">
        @forelse($siteVisits as $siteVisit)
        <article class="rounded-xl border border-slate-200 p-4">
            <div class="flex flex-wrap justify-between gap-2"><p class="font-semibold">{{ $siteVisit->visit_date->format('d M Y') }}</p><p class="text-xs text-slate-500">Recorded by {{ $siteVisit->creator->name }}</p></div>
            @if($siteVisit->details)<p class="mt-2 whitespace-pre-wrap break-words text-sm">{{ $siteVisit->details }}</p>@endif
        </article>
        @empty
        <p class="py-4 text-sm text-slate-500">No completed site visits recorded.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $siteVisits->links() }}</div>
</section>
<section id="followup-history" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft"><h2 class="mb-4 text-lg font-bold">Follow-up history</h2><p class="mb-4 text-xs text-slate-500">Oldest first · {{ config('app.timezone') }}. The most recent follow-up time determines the current outcome and callback.</p><div class="space-y-4">@forelse($followups as $followup)<div class="rounded-xl border border-slate-200 p-4"><div class="flex flex-wrap justify-between gap-2"><p class="font-semibold">{{ $followup->outcome }}</p><p class="text-sm text-slate-500">{{ $followup->followup_at->format('d M Y H:i') }}</p></div><p class="mt-2 whitespace-pre-wrap break-words text-sm">{{ $followup->remarks??'No notes' }}</p><p class="mt-3 text-xs text-slate-500">By {{ $followup->creator->name }} · Recorded {{ $followup->created_at->format('d M Y H:i') }}@if($followup->next_followup_at) · Callback {{ $followup->next_followup_at->format('d M Y H:i') }}@endif</p></div>@empty<p class="py-6 text-center text-sm text-slate-500">No follow-ups recorded.</p>@endforelse</div><div class="mt-5">{{ $followups->links() }}</div></section>
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft"><h2 class="mb-4 text-lg font-bold">Assignment history</h2><div class="divide-y divide-slate-100">@forelse($assignments as $assignment)<div class="py-3 text-sm"><p>{{ $assignment->previousEmployee?->name??'Unassigned' }} → {{ $assignment->newEmployee?->name??'Unassigned' }}</p><p class="mt-1 text-xs text-slate-500">{{ $assignment->actor->name }} · {{ $assignment->created_at->format('d M Y H:i') }}</p><p class="mt-1 break-words">{{ $assignment->reason }}</p></div>@empty<p class="py-4 text-sm text-slate-500">No assignment changes.</p>@endforelse</div><div class="mt-5">{{ $assignments->links() }}</div></section>
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft"><h2 class="mb-4 text-lg font-bold">Activity and status history</h2><div class="divide-y divide-slate-100">@foreach($audits as $audit)<div class="py-3 text-sm"><p>{{ str_replace('_',' ',$audit->event) }} · {{ $audit->actor->name }}</p><p class="text-xs text-slate-500">{{ $audit->created_at->format('d M Y H:i') }}</p>@if($audit->event==='status_changed')<p>{{ $audit->metadata['from'] }} → {{ $audit->metadata['to'] }}: {{ $audit->metadata['reason'] }}</p>@endif</div>@endforeach</div><div class="mt-5">{{ $audits->links() }}</div></section>
@can('viewMobile',$lead)
@if($lead->source_data)
<details class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft"><summary class="cursor-pointer font-semibold">Original spreadsheet details</summary><p class="mt-3 text-xs text-slate-500">Original values are retained for reference. Sales statuses and dates are not treated as new lead status or follow-up instructions.</p><dl class="mt-4 grid gap-4 sm:grid-cols-2">@foreach($lead->source_data as $label=>$value)<div><dt class="text-xs font-semibold text-slate-500">{{ $label }}</dt><dd class="mt-1 whitespace-pre-wrap break-words text-sm">{{ $value }}</dd></div>@endforeach</dl></details>
@endif
@endcan
@endsection
