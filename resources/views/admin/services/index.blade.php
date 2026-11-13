@extends('layouts.app')
@section('title', 'Homepage Services')
@section('heading', 'Homepage Services')
@section('breadcrumb', 'Homepage Services')
@section('content')
<section class="page-intro">
    <h2 class="text-2xl font-bold">Homepage services</h2>
    <p class="mt-1 text-sm">Manage the service cards displayed on the public homepage. Each service has an image, title, and description.</p>
</section>

<form method="POST" action="{{ route('admin.services.store') }}" enctype="multipart/form-data" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-soft sm:p-7">
    @csrf
    <h2 class="text-lg font-bold text-navy-950">Add a service</h2>
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label for="new-service-title" class="mb-1.5 block text-sm font-semibold">Title *</label>
            <input id="new-service-title" class="field" name="title" value="{{ old('title') }}" maxlength="150" required>
        </div>
        <div>
            <label for="new-service-image" class="mb-1.5 block text-sm font-semibold">Image *</label>
            <input id="new-service-image" class="field" type="file" name="image" accept=".jpg,.jpeg,.png,.webp" required>
            <p class="mt-1 text-xs text-slate-500">JPG, PNG or WebP; up to 5 MB.</p>
        </div>
        <div class="md:col-span-2">
            <label for="new-service-description" class="mb-1.5 block text-sm font-semibold">Description *</label>
            <textarea id="new-service-description" class="field" name="description" rows="3" maxlength="2000" required>{{ old('description') }}</textarea>
        </div>
    </div>
    <div class="flex justify-end">
        <button class="btn btn-primary">Add service</button>
    </div>
</form>

<section class="space-y-4">
    <div>
        <h2 class="text-xl font-bold text-navy-950">Current services</h2>
        <p class="mt-1 text-sm text-slate-500">Edit each service's image, title, and description below.</p>
    </div>
    @if($services->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">No services yet. Add one using the form above.</div>
    @else
        <div class="grid gap-5 lg:grid-cols-2">
            @foreach($services as $service)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft">
                    <form method="POST" action="{{ route('admin.services.update', $service) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <img class="h-52 w-full rounded-xl object-cover" src="{{ $service->image_url }}" alt="{{ $service->title }} preview">
                        <div>
                            <label for="service-title-{{ $service->id }}" class="mb-1.5 block text-sm font-semibold">Title *</label>
                            <input id="service-title-{{ $service->id }}" class="field" name="title" value="{{ $service->title }}" maxlength="150" required>
                        </div>
                        <div>
                            <label for="service-description-{{ $service->id }}" class="mb-1.5 block text-sm font-semibold">Description *</label>
                            <textarea id="service-description-{{ $service->id }}" class="field" name="description" rows="4" maxlength="2000" required>{{ $service->description }}</textarea>
                        </div>
                        <div>
                            <label for="service-image-{{ $service->id }}" class="mb-1.5 block text-sm font-semibold">Replace image</label>
                            <input id="service-image-{{ $service->id }}" class="field" type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
                            <p class="mt-1 text-xs text-slate-500">Leave empty to keep the current image. JPG, PNG or WebP; up to 5 MB.</p>
                        </div>
                        <div class="flex justify-end">
                            <button class="btn btn-primary">Save service</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('admin.services.destroy', $service) }}" class="mt-3 flex justify-end" onsubmit="return confirm('Remove this service from the homepage?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger">Remove service</button>
                    </form>
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection
