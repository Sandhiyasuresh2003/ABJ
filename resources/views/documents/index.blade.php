@extends('layouts.app')
@section('title', 'Documents')
@section('heading', 'Documents')
@section('breadcrumb', 'Documents')
@section('content')
<div class="page-intro">
    <h2 class="text-2xl font-bold">Property documents</h2>
    <p class="mt-1 text-sm">Upload and download EC and Patta Chitta documents. Available to admins and owners.</p>
</div>

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft sm:p-7">
    <h2 class="text-xl font-bold text-navy-950">Upload document</h2>
    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="document-title" class="mb-1.5 block text-sm font-semibold">Document title *</label>
                <input id="document-title" class="field" name="title" value="{{ old('title') }}" maxlength="150" placeholder="e.g. Anna Nagar Plot 12" required>
            </div>
            <div>
                <label for="document-type" class="mb-1.5 block text-sm font-semibold">Document type *</label>
                <select id="document-type" class="field" name="type" required>
                    <option value="">Select document type</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $selectedType) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label for="document-file" class="mb-1.5 block text-sm font-semibold">File *</label>
            <input id="document-file" class="field" type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" aria-describedby="document-help" required>
            <p id="document-help" class="mt-2 text-xs text-slate-500">PDF, JPG or PNG. Maximum file size: 10 MB.</p>
        </div>
        <div class="flex justify-end border-t border-slate-100 pt-4"><button class="btn btn-primary">Upload document</button></div>
    </form>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft sm:p-7">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <h2 class="text-xl font-bold text-navy-950">Uploaded documents</h2>
        <form method="GET" action="{{ route('documents.index') }}" class="flex items-end gap-2">
            <div>
                <label for="filter-type" class="mb-1.5 block text-sm font-semibold">Document type</label>
                <select id="filter-type" class="field" name="type">
                    <option value="">All documents</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-light">Filter</button>
        </form>
    </div>
    <div class="mt-5 overflow-x-auto rounded-xl border border-slate-200">
        <table class="w-full min-w-[700px] text-left">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                <tr><th scope="col" class="px-4 py-3">Document</th><th scope="col" class="px-4 py-3">Type</th><th scope="col" class="px-4 py-3">Uploaded by</th><th scope="col" class="px-4 py-3">Uploaded on</th><th scope="col" class="px-4 py-3">Download</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($documents as $document)
                    <tr>
                        <td class="px-4 py-3"><p class="text-sm font-semibold">{{ $document->title }}</p><p class="mt-1 break-all text-xs text-slate-500">{{ $document->original_name }} &middot; {{ number_format($document->size / 1024, 1) }} KB</p></td>
                        <td class="px-4 py-3 text-sm">{{ $types[$document->type] }}</td>
                        <td class="px-4 py-3 text-sm">{{ $document->uploader?->name ?? 'Former user' }}</td>
                        <td class="px-4 py-3 text-sm">{{ $document->created_at->format('d M Y, h:i A') }}</td>
                        <td class="px-4 py-3"><a class="btn btn-light" href="{{ route('documents.download', $document) }}" aria-label="Download {{ $document->title }}">Download</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">No documents found. Upload an EC or Patta Chitta document to get started.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $documents->links() }}</div>
</section>
@endsection
