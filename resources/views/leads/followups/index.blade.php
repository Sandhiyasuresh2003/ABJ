@extends('layouts.app')
@section('title','Follow-up history')
@section('heading','Follow-up history')
@section('breadcrumb','Leads / Follow-ups')
@section('content')
@include('leads.nav')

<section class="rounded-2xl border border-white/70 bg-white/70 p-6 shadow-xl shadow-slate-900/10 backdrop-blur-md">
    <div class="flex flex-wrap items-start justify-between gap-5">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.16em] text-emerald-800">Lead follow-ups</p>
            <h2 class="mt-1 text-2xl font-bold text-navy-950">{{ $lead->lead_name ?: 'Name not provided' }}</h2>
        </div>
        <a class="btn btn-light" href="{{ route('leads.index') }}">Back to all leads</a>
    </div>
    <dl class="mt-5 grid gap-4 border-t border-white/70 pt-5 sm:grid-cols-2">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Mobile number</dt>
            <dd class="mt-1 font-medium text-slate-900">{{ $lead->displayMobile(auth()->user()) }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Assigned employee</dt>
            <dd class="mt-1 font-medium text-slate-900">{{ $lead->employee?->name ?? 'Unassigned' }}</dd>
        </div>
    </dl>
</section>

<section class="overflow-hidden rounded-2xl border border-white/65 bg-white/65 shadow-xl shadow-slate-900/10 backdrop-blur-md">
    <div class="border-b border-white/60 px-5 py-4">
        <h3 class="font-bold text-navy-950">Requirement updates by date</h3>
        <p class="mt-1 text-sm text-slate-600">Requirement notes separated using dates found in the saved text.</p>
    </div>
    <div class="max-h-[45vh] overflow-auto">
        <table class="w-full text-left text-sm">
            <thead class="sticky top-0 z-10 bg-emerald-950/90 text-xs uppercase tracking-wide text-emerald-50">
                <tr>
                    <th class="whitespace-nowrap px-4 py-3">Date</th>
                    <th class="px-4 py-3">Requirement update</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/60">
                @forelse($requirementEntries as $entry)
                <tr class="bg-white/40 align-top transition hover:bg-white/60">
                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-800">{{ $entry['date'] ?? 'No date' }}</td>
                    <td class="whitespace-pre-wrap break-words px-4 py-3 text-slate-700">{{ $entry['content'] ?: 'No note recorded.' }}</td>
                </tr>
                @empty
                <tr><td colspan="2" class="bg-white/40 px-5 py-10 text-center text-slate-600">No requirement notes recorded for this lead.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="overflow-hidden rounded-2xl border border-white/70 bg-white/70 shadow-xl shadow-slate-900/10 backdrop-blur-md">
    <div class="border-b border-white/70 px-5 py-4">
        <h3 class="font-bold text-navy-950">Follow-up records</h3>
        <p class="mt-1 text-sm text-slate-600">{{ $followups->total() }} total follow-ups · newest first</p>
    </div>
    <div class="max-h-[60vh] overflow-auto">
        <table class="w-full text-left text-sm">
            <thead class="sticky top-0 z-10 bg-emerald-950/90 text-xs uppercase tracking-wide text-emerald-50">
                <tr>
                    <th class="whitespace-nowrap px-4 py-3">Follow-up date</th>
                    <th class="px-4 py-3">Outcome</th>
                    <th class="px-4 py-3">Follow-up content</th>
                    <th class="whitespace-nowrap px-4 py-3">Next follow-up</th>
                    <th class="px-4 py-3">Recorded by</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/70">
                @forelse($followups as $followup)
                <tr class="bg-white/45 align-top transition hover:bg-white/75">
                    <td class="whitespace-nowrap px-4 py-4">{{ $followup->followup_at->format('d M Y, h:i A') }}</td>
                    <td class="px-4 py-4 font-semibold text-slate-900">{{ $followup->outcome }}</td>
                    <td class="max-w-xl whitespace-pre-wrap break-words px-4 py-4">{{ $followup->remarks ?: 'No remarks added.' }}</td>
                    <td class="whitespace-nowrap px-4 py-4">{{ $followup->next_followup_at?->format('d M Y, h:i A') ?? '—' }}</td>
                    <td class="px-4 py-4">{{ $followup->creator?->name ?? 'Former user' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="bg-white/45 px-5 py-12 text-center text-slate-600">No follow-ups recorded for this lead.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-white/70 p-5">{{ $followups->links() }}</div>
</section>
@endsection
