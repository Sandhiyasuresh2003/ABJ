@extends('layouts.app')
@section('title',$module)
@section('breadcrumb',$module)
@section('heading',$module)
@section('content')
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft sm:p-8">
    <p class="text-xs font-bold uppercase tracking-[.18em] text-gold-600">Coming soon</p>
    <h2 class="mt-2 text-xl font-bold text-navy-950">{{ $module }} module</h2>
    <p class="mt-2 text-sm text-slate-500">This section is not available yet. Existing workspace features remain available from the sidebar.</p>
</section>
@endsection
