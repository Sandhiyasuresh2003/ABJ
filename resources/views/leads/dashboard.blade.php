@extends('layouts.app')
@section('title','Leads dashboard')
@section('heading','Leads dashboard')
@section('breadcrumb','Leads')
@section('content')
@include('leads.nav')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @foreach([
        ['Total leads', $total, 'All enquiries', 'bg-emerald-700'],
        ['Wanted leads', $wanted, 'Interested customers', 'bg-teal-600'],
        ['Unwanted leads', $unwanted, 'Not proceeding', 'bg-amber-600'],
    ] as [$label, $value, $description, $accent])
    <article class="group relative overflow-hidden rounded-2xl border border-white/70 bg-white/75 p-5 shadow-lg shadow-slate-900/10 backdrop-blur-md transition duration-200 hover:-translate-y-1 hover:bg-white/85">
        <span class="absolute inset-y-0 left-0 w-1 {{ $accent }}"></span>
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-slate-600">{{ $label }}</p>
                <p class="mt-3 text-4xl font-bold tracking-tight text-navy-950">{{ number_format($value) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $description }}</p>
            </div>
            <span class="mt-1 h-3 w-3 rounded-full {{ $accent }} ring-4 ring-white/70"></span>
        </div>
    </article>
    @endforeach
</div>
@can('import', App\Models\Lead::class)
<section class="relative isolate flex flex-col gap-5 overflow-hidden rounded-2xl border border-white/20 bg-gradient-to-r from-emerald-950/95 via-emerald-900/90 to-teal-800/85 p-6 text-white shadow-xl shadow-emerald-950/20 backdrop-blur-md sm:flex-row sm:items-center sm:justify-between">
    <div aria-hidden="true" class="pointer-events-none absolute -right-8 -top-16 h-56 w-56 rounded-full border-[28px] border-white/10"></div>
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.18em] text-emerald-200">Lead management</p>
        <h2 class="mt-1 text-xl font-bold">Bring your leads together</h2>
        <p class="mt-1 text-sm text-emerald-50/80">Upload a spreadsheet, review the records, and import them securely.</p>
    </div>
    <a class="btn relative z-10 shrink-0 border border-white/70 bg-white px-5 text-emerald-950 shadow-sm transition hover:bg-emerald-50" href="{{ route('leads.imports.create') }}">Import leads <span aria-hidden="true" class="ml-2">→</span></a>
</section>
@endcan
@endsection
