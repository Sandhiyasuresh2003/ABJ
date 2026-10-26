@extends('layouts.app')
@section('title','Leads dashboard')
@section('heading','Leads dashboard')
@section('breadcrumb','Leads')
@section('content')
@include('leads.nav')
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    @foreach(['Total leads'=>$total,'Active'=>$active,'Inactive'=>$inactive,'Unassigned'=>$unassigned,'Callbacks due or overdue'=>$callbacks] as $label=>$value)
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft"><p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-2 text-3xl font-bold text-navy-950">{{ $value }}</p></div>
    @endforeach
</div>
<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft">
    <h2 class="mb-4 text-lg font-bold">Recent leads</h2>
    <div class="divide-y divide-slate-100">
    @forelse($recent as $lead)
    <a class="flex flex-wrap items-center justify-between gap-3 py-4" href="{{ route('leads.show',$lead) }}"><span class="font-semibold">{{ $lead->lead_name }}<span class="ml-3 text-sm font-normal text-slate-500">{{ $lead->location }}</span></span><span class="text-sm">{{ $lead->status }} · {{ $lead->employee?->name ?? 'Unassigned' }}</span></a>
    @empty <p class="py-8 text-center text-sm text-slate-500">No leads are available in your workspace.</p> @endforelse
    </div>
</div>
@endsection
