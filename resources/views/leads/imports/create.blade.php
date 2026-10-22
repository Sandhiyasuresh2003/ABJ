@extends('layouts.app')
@section('title','Import leads')
@section('heading','Import leads')
@section('breadcrumb','Leads / Import')
@section('content')
@include('leads.nav')
<div class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-soft"><h2 class="text-lg font-bold">Upload a lead spreadsheet</h2><p class="mt-2 text-sm text-slate-500">Upload → select worksheet and columns → review validation → confirm import. No leads are saved before confirmation.</p>
<a class="btn btn-light mt-5" href="{{ route('leads.imports.template') }}">Download Excel template</a>
<p class="mt-4 text-sm text-slate-500">The template uses Lead Name, Mobile Number, Location, Requirement and optional Assigned Employee. Your existing sales workbook is also supported through column mapping. Use employee code or email for an unambiguous assignment.</p>
<form method="POST" action="{{ route('leads.imports.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5">@csrf<div><label for="file" class="mb-2 block text-sm font-semibold">Excel or CSV file *</label><input id="file" class="field" type="file" name="file" accept=".xlsx,.csv" required></div><p class="text-xs text-slate-500">XLSX or UTF-8 CSV only. Maximum {{ config('leads.max_upload_kb')/1024 }} MB, {{ number_format(config('leads.max_rows')) }} data rows per worksheet, {{ config('leads.max_columns') }} columns. Import one worksheet at a time. Macros, embedded objects and formula values are not accepted. Uploads and previews expire after {{ config('leads.preview_hours') }} hours.</p><button class="btn btn-primary">Upload and choose columns</button></form></div>
@endsection
