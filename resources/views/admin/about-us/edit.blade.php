@extends('layouts.app')
@section('title', 'About Us')
@section('heading', 'About Us')
@section('breadcrumb', 'About Us')
@section('content')
<section class="page-intro">
    <h2 class="text-2xl font-bold">Front page About Us section</h2>
    <p class="mt-1 text-sm">Update the label, heading, description and the two images displayed on the public landing page.</p>
</section>

<form method="POST" action="{{ route('admin.about-us.update') }}" enctype="multipart/form-data" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-soft sm:p-7">
    @csrf
    @method('PUT')

    <div>
        <label for="about-label" class="mb-1.5 block text-sm font-semibold">Label *</label>
        <input id="about-label" class="field" name="label" value="{{ old('label', $settings->label) }}" maxlength="100" required>
        <p class="mt-1 text-xs text-slate-500">Shown above the About Us heading on the landing page.</p>
    </div>

    <div>
        <label for="about-heading" class="mb-1.5 block text-sm font-semibold">Heading *</label>
        <input id="about-heading" class="field" name="heading" value="{{ old('heading', $settings->heading) }}" maxlength="150" required>
    </div>

    <div>
        <label for="about-description" class="mb-1.5 block text-sm font-semibold">Description *</label>
        <textarea id="about-description" class="field" name="description" rows="6" maxlength="2000" required>{{ old('description', $settings->description) }}</textarea>
        <p class="mt-1 text-xs text-slate-500">Paragraph shown below the heading. Line breaks are kept. Up to 2000 characters.</p>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        @foreach(['landing_image_one' => 'Landing image 1', 'landing_image_two' => 'Landing image 2'] as $field => $label)
            <div class="space-y-3">
                <label for="{{ $field }}" class="block text-sm font-semibold">{{ $label }}</label>
                @if($settings->{$field})
                    <img class="h-52 w-full rounded-xl border border-slate-200 object-cover" src="{{ asset('storage/'.$settings->{$field}) }}" alt="{{ $label }} preview">
                @else
                    <div class="flex h-52 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-sm text-slate-500">No image uploaded</div>
                @endif
                <input id="{{ $field }}" class="field" type="file" name="{{ $field }}" accept=".jpg,.jpeg,.png,.webp">
                <p class="text-xs text-slate-500">JPG, PNG or WebP; up to 5 MB. Leave empty to keep the current image.</p>
            </div>
        @endforeach
    </div>

    <div class="flex justify-end border-t border-slate-100 pt-4">
        <button class="btn btn-primary">Save About Us</button>
    </div>
</form>
@endsection
