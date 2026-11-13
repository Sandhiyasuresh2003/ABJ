@extends('layouts.app')
@section('title','Import results')
@section('heading','Import results')
@section('breadcrumb','Leads / Import')
@section('content')
@include('leads.nav')
<div class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-soft"><h2 class="text-lg font-bold">{{ $import->original_filename }}</h2><p class="mt-2 text-sm text-slate-500">{{ ucfirst($import->status) }} · Uploaded by {{ $import->uploader->name }} on {{ $import->created_at->format('d M Y H:i') }}</p><dl class="mt-6 grid grid-cols-2 gap-5 sm:grid-cols-4">@foreach(['Total rows'=>$import->total_rows,'Imported'=>$import->imported_rows,'Duplicates'=>$import->duplicate_rows,'Failed / invalid'=>$import->failed_rows] as $label=>$value)<div><dt class="text-xs text-slate-500">{{ $label }}</dt><dd class="mt-1 text-2xl font-bold">{{ $value }}</dd></div>@endforeach</dl><p class="mt-6 text-sm text-slate-500">Duplicates detected at confirmation are included in the final totals. Invalid rows and employees who became ineligible were skipped. Completed source uploads and preview details are removed; this summary remains in import history.</p></div>
@endsection
