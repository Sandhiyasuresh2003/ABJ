@extends('layouts.app')
@section('title','Import history')
@section('heading','Import history')
@section('breadcrumb','Leads / Imports')
@section('content')
@include('leads.nav')
<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft"><div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50"><tr>@foreach(['Uploaded','File','User','Status','Total','Imported','Duplicates','Failed','Action'] as $label)<th class="whitespace-nowrap px-4 py-3">{{ $label }}</th>@endforeach</tr></thead><tbody class="divide-y divide-slate-100">@forelse($imports as $import)<tr><td class="px-4 py-4">{{ $import->created_at->format('d M Y H:i') }}</td><td class="max-w-xs break-words px-4 py-4">{{ $import->original_filename }}</td><td class="px-4 py-4">{{ $import->uploader->name }}</td><td class="px-4 py-4">{{ ucfirst($import->status) }}</td>@foreach(['total_rows','imported_rows','duplicate_rows','failed_rows'] as $field)<td class="px-4 py-4">{{ $import->$field }}</td>@endforeach<td class="px-4 py-4">@if(in_array($import->status,['completed','expired']) || ($import->uploaded_by===auth()->id() && auth()->user()->can('import',App\Models\Lead::class)))<a class="text-navy-900 underline" href="{{ route('leads.imports.show',$import) }}">{{ in_array($import->status,['completed','expired'])?'Results':'Continue' }}</a>@else — @endif</td></tr>@empty<tr><td colspan="9" class="p-10 text-center text-slate-500">No import history yet.</td></tr>@endforelse</tbody></table></div><div class="p-5">{{ $imports->links() }}</div></div>
@endsection
