@extends('layouts.app')
@section('title','Map lead columns')
@section('heading','Map lead columns')
@section('breadcrumb','Leads / Import')
@section('content')
@include('leads.nav')
<div class="max-w-4xl rounded-2xl border border-slate-200 bg-white p-6 shadow-soft"><h2 class="text-lg font-bold">{{ $import->original_filename }}</h2><p class="mt-2 text-sm text-slate-500">Choose a worksheet and match columns. Mobile number is required; all other fields are optional.</p>
<form method="GET" action="{{ route('leads.imports.show',$import) }}" class="my-6 flex flex-wrap items-end gap-3"><div class="min-w-48 flex-1"><label for="sheet" class="mb-1 block text-sm font-semibold">Worksheet</label><select id="sheet" class="field" name="sheet">@foreach($sheets as $index=>$info)<option value="{{ $index }}" @selected($sheet===$index)>{{ $info['worksheetName'] }}</option>@endforeach</select></div><button class="btn btn-light">Load worksheet columns</button></form>
<form method="POST" action="{{ route('leads.imports.preview',$import) }}" class="space-y-5">@csrf<input type="hidden" name="sheet" value="{{ $sheet }}">
@foreach(['mobile_number'=>'Mobile number *','lead_name'=>'Lead name (optional)','location'=>'Location (optional)','requirement'=>'Requirement (optional)','assigned_to'=>'Assigned employee (optional)','project_category'=>'Project category (optional)'] as $field=>$label)
<div><label for="map-{{ $field }}" class="mb-1 block text-sm font-semibold">{{ $label }}</label><select id="map-{{ $field }}" class="field" name="mapping[{{ $field }}]" @required($field==='mobile_number')><option value="">Do not import this column</option>@foreach($headers as $column=>$header)@if(trim($header)!=='')<option value="{{ $column }}" @selected((string)old('mapping.'.$field,$suggestions[$field]??'')===(string)$column)>{{ PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column+1) }} — {{ $header }}</option>@endif @endforeach</select></div>
@endforeach
<p class="text-xs text-slate-500">Rows with a missing or invalid mobile number will be rejected. Blank optional fields are saved as empty. If project category is not mapped or is blank, it defaults to Agri. Existing sales statuses, remarks, dates and other source columns are preserved as reference details; they do not change lead status or create follow-ups.</p>
<button class="btn btn-primary">Validate and preview</button><a class="btn btn-light" href="{{ route('leads.imports.create') }}">Upload another file</a>
</form></div>
@endsection
