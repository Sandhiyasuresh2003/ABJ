@extends('layouts.app')
@section('title', 'Client Feedback')
@section('heading', 'Client Feedback')
@section('breadcrumb', 'Client Feedback')
@section('content')
<section class="page-intro">
    <h2 class="text-2xl font-bold">Homepage client feedback</h2>
    <p class="mt-1 text-sm">Add and manage the customer photo, name, role, and feedback shown in the homepage testimonial carousel.</p>
</section>

<form method="POST" action="{{ route('admin.client-feedback.store') }}" enctype="multipart/form-data" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-soft sm:p-7">
    @csrf
    <h2 class="text-lg font-bold text-navy-950">Add client feedback</h2>
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label for="new-feedback-name" class="mb-1.5 block text-sm font-semibold">Client name *</label>
            <input id="new-feedback-name" class="field" name="name" value="{{ old('name') }}" maxlength="150" required>
        </div>
        <div>
            <label for="new-feedback-role" class="mb-1.5 block text-sm font-semibold">Role *</label>
            <input id="new-feedback-role" class="field" name="role" value="{{ old('role', 'Client') }}" maxlength="100" required>
        </div>
        <div>
            <label for="new-feedback-image" class="mb-1.5 block text-sm font-semibold">Client photo *</label>
            <input id="new-feedback-image" class="field" type="file" name="image" accept=".jpg,.jpeg,.png,.webp" required>
            <p class="mt-1 text-xs text-slate-500">JPG, PNG or WebP; up to 5 MB.</p>
        </div>
        <div>
            <label for="new-feedback-text" class="mb-1.5 block text-sm font-semibold">Feedback *</label>
            <textarea id="new-feedback-text" class="field" name="feedback" rows="3" maxlength="2000" required>{{ old('feedback') }}</textarea>
        </div>
    </div>
    <div class="flex justify-end">
        <button class="btn btn-primary">Add feedback</button>
    </div>
</form>

<section class="space-y-4">
    <div>
        <h2 class="text-xl font-bold text-navy-950">Current feedback</h2>
        <p class="mt-1 text-sm text-slate-500">Edit or remove the feedback items shown on the homepage.</p>
    </div>
    @if($testimonials->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">No client feedback yet. Add one using the form above.</div>
    @else
        <div class="grid gap-5 lg:grid-cols-2">
            @foreach($testimonials as $testimonial)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft">
                    <form method="POST" action="{{ route('admin.client-feedback.update', $testimonial) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div class="flex items-center gap-4">
                            <img class="h-20 w-20 rounded-full border-4 border-emerald-600 object-cover" src="{{ $testimonial->image_url }}" alt="{{ $testimonial->name }} photo">
                            <div>
                                <p class="font-semibold text-navy-950">{{ $testimonial->name }}</p>
                                <p class="text-sm text-slate-500">{{ $testimonial->role }}</p>
                            </div>
                        </div>
                        <div>
                            <label for="feedback-name-{{ $testimonial->id }}" class="mb-1.5 block text-sm font-semibold">Client name *</label>
                            <input id="feedback-name-{{ $testimonial->id }}" class="field" name="name" value="{{ $testimonial->name }}" maxlength="150" required>
                        </div>
                        <div>
                            <label for="feedback-role-{{ $testimonial->id }}" class="mb-1.5 block text-sm font-semibold">Role *</label>
                            <input id="feedback-role-{{ $testimonial->id }}" class="field" name="role" value="{{ $testimonial->role }}" maxlength="100" required>
                        </div>
                        <div>
                            <label for="feedback-text-{{ $testimonial->id }}" class="mb-1.5 block text-sm font-semibold">Feedback *</label>
                            <textarea id="feedback-text-{{ $testimonial->id }}" class="field" name="feedback" rows="4" maxlength="2000" required>{{ $testimonial->feedback }}</textarea>
                        </div>
                        <div>
                            <label for="feedback-image-{{ $testimonial->id }}" class="mb-1.5 block text-sm font-semibold">Replace photo</label>
                            <input id="feedback-image-{{ $testimonial->id }}" class="field" type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
                            <p class="mt-1 text-xs text-slate-500">Leave empty to keep the current photo. JPG, PNG or WebP; up to 5 MB.</p>
                        </div>
                        <div class="flex justify-end">
                            <button class="btn btn-primary">Save feedback</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('admin.client-feedback.destroy', $testimonial) }}" class="mt-3 flex justify-end" onsubmit="return confirm('Remove this client feedback from the homepage?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger">Remove feedback</button>
                    </form>
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection
