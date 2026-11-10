@extends('layouts.app')
@section('title','Import leads')
@section('heading','Import leads')
@section('breadcrumb','Leads / Import')
@section('content')
@include('leads.nav')
<div class="relative isolate max-w-3xl overflow-hidden rounded-2xl border border-white/70 bg-white/65 p-6 shadow-xl shadow-slate-900/10 backdrop-blur-md"><div aria-hidden="true" class="pointer-events-none absolute -right-14 -top-20 -z-10 h-64 w-64 rounded-full border-[32px] border-emerald-800/5"></div><h2 class="text-lg font-bold text-navy-950">Upload a lead spreadsheet</h2><p class="mt-2 text-sm text-slate-600">Upload → select worksheet and columns → review validation → confirm import. No leads are saved before confirmation.</p>
<a class="btn btn-light mt-5" href="{{ route('leads.imports.template') }}">Download sample XLSX with headers</a>
<form method="POST" action="{{ route('leads.imports.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5">@csrf<div><label for="file" class="mb-2 block text-sm font-semibold">XLSX or CSV file *</label><input id="file" class="field" type="file" name="file" accept=".xlsx,.csv" required></div><p class="text-xs text-slate-500">Mobile number is the only required lead field. Name, requirement and assignment are optional. XLSX or UTF-8 CSV only. Maximum {{ config('leads.max_upload_kb')/1024 }} MB, {{ number_format(config('leads.max_rows')) }} data rows per worksheet, {{ config('leads.max_columns') }} columns. Import one worksheet at a time. Macros, embedded objects and formula values are not accepted. Uploads and previews expire after {{ config('leads.preview_hours') }} hours.</p><button class="btn btn-primary">Upload and choose columns</button></form></div>
@endsection
