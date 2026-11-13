@extends('layouts.app')
@section('title', 'Latest News')
@section('heading', 'Latest News')
@section('breadcrumb', 'Latest News')
@section('content')
<section class="page-intro">
    <h2 class="text-2xl font-bold">Homepage latest news</h2>
    <p class="mt-1 text-sm">Manage news cards and their full project details and image galleries.</p>
</section>

<form method="POST" action="{{ route('admin.latest-news.store') }}" enctype="multipart/form-data" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-soft sm:p-7">
    @csrf
    <h2 class="text-lg font-bold text-navy-950">Add a news item</h2>
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label for="new-news-title" class="mb-1.5 block text-sm font-semibold">Title *</label>
            <input id="new-news-title" class="field" name="title" value="{{ old('title') }}" maxlength="150" required>
        </div>
        <div>
            <label for="new-news-date" class="mb-1.5 block text-sm font-semibold">Publication date *</label>
            <input id="new-news-date" class="field" type="date" name="published_at" value="{{ old('published_at', now()->toDateString()) }}" required>
        </div>
        <div>
            <label for="new-news-project" class="mb-1.5 block text-sm font-semibold">Project name *</label>
            <input id="new-news-project" class="field" name="project_name" value="{{ old('project_name') }}" maxlength="150" required>
        </div>
        <div>
            <label for="new-news-location" class="mb-1.5 block text-sm font-semibold">Project location *</label>
            <input id="new-news-location" class="field" name="project_location" value="{{ old('project_location') }}" maxlength="255" required>
        </div>
        <div>
            <label for="new-news-image" class="mb-1.5 block text-sm font-semibold">Image *</label>
            <input id="new-news-image" class="field" type="file" name="image" accept=".jpg,.jpeg,.png,.webp" required>
            <p class="mt-1 text-xs text-slate-500">JPG, PNG or WebP; up to 5 MB.</p>
        </div>
        <div>
            <label for="new-news-description" class="mb-1.5 block text-sm font-semibold">Description *</label>
            <textarea id="new-news-description" class="field" name="description" rows="3" maxlength="2000" required>{{ old('description') }}</textarea>
        </div>
        <div class="md:col-span-2">
            <label for="new-news-project-about" class="mb-1.5 block text-sm font-semibold">About the project *</label>
            <textarea id="new-news-project-about" class="field" name="project_about" rows="5" maxlength="10000" required>{{ old('project_about') }}</textarea>
        </div>
        <div class="md:col-span-2">
            <label for="new-news-gallery" class="mb-1.5 block text-sm font-semibold">Project gallery</label>
            <input id="new-news-gallery" class="field" type="file" name="gallery_images[]" accept=".jpg,.jpeg,.png,.webp" multiple>
            <p class="mt-1 text-xs text-slate-500">Add up to 20 JPG, PNG or WebP images, maximum 5 MB each.</p>
        </div>
    </div>
    <div class="flex justify-end">
        <button class="btn btn-primary">Add news</button>
    </div>
</form>

<section class="space-y-4">
    <div>
        <h2 class="text-xl font-bold text-navy-950">Current news</h2>
        <p class="mt-1 text-sm text-slate-500">Homepage news cards are displayed from newest publication date to oldest.</p>
    </div>
    @if($newsItems->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">No news items yet. Add one using the form above.</div>
    @else
        <div class="grid gap-5 lg:grid-cols-2">
            @foreach($newsItems as $newsItem)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft">
                    <form method="POST" action="{{ route('admin.latest-news.update', $newsItem) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <img class="h-52 w-full rounded-xl object-cover" src="{{ $newsItem->image_url }}" alt="{{ $newsItem->title }} preview">
                        <div>
                            <label for="news-title-{{ $newsItem->id }}" class="mb-1.5 block text-sm font-semibold">Title *</label>
                            <input id="news-title-{{ $newsItem->id }}" class="field" name="title" value="{{ $newsItem->title }}" maxlength="150" required>
                        </div>
                        <div>
                            <label for="news-date-{{ $newsItem->id }}" class="mb-1.5 block text-sm font-semibold">Publication date *</label>
                            <input id="news-date-{{ $newsItem->id }}" class="field" type="date" name="published_at" value="{{ $newsItem->published_at->toDateString() }}" required>
                        </div>
                        <div>
                            <label for="news-project-{{ $newsItem->id }}" class="mb-1.5 block text-sm font-semibold">Project name *</label>
                            <input id="news-project-{{ $newsItem->id }}" class="field" name="project_name" value="{{ $newsItem->project_name }}" maxlength="150" required>
                        </div>
                        <div>
                            <label for="news-location-{{ $newsItem->id }}" class="mb-1.5 block text-sm font-semibold">Project location *</label>
                            <input id="news-location-{{ $newsItem->id }}" class="field" name="project_location" value="{{ $newsItem->project_location }}" maxlength="255" required>
                        </div>
                        <div>
                            <label for="news-description-{{ $newsItem->id }}" class="mb-1.5 block text-sm font-semibold">Description *</label>
                            <textarea id="news-description-{{ $newsItem->id }}" class="field" name="description" rows="4" maxlength="2000" required>{{ $newsItem->description }}</textarea>
                        </div>
                        <div>
                            <label for="news-project-about-{{ $newsItem->id }}" class="mb-1.5 block text-sm font-semibold">About the project *</label>
                            <textarea id="news-project-about-{{ $newsItem->id }}" class="field" name="project_about" rows="5" maxlength="10000" required>{{ $newsItem->project_about }}</textarea>
                        </div>
                        <div>
                            <label for="news-image-{{ $newsItem->id }}" class="mb-1.5 block text-sm font-semibold">Replace image</label>
                            <input id="news-image-{{ $newsItem->id }}" class="field" type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
                            <p class="mt-1 text-xs text-slate-500">Leave empty to keep the current image. JPG, PNG or WebP; up to 5 MB.</p>
                        </div>
                        <div>
                            <label for="news-gallery-{{ $newsItem->id }}" class="mb-1.5 block text-sm font-semibold">Add gallery images</label>
                            <input id="news-gallery-{{ $newsItem->id }}" class="field" type="file" name="gallery_images[]" accept=".jpg,.jpeg,.png,.webp" multiple>
                            <p class="mt-1 text-xs text-slate-500">Select up to 20 additional JPG, PNG or WebP images, maximum 5 MB each.</p>
                        </div>
                        <div class="flex justify-end">
                            <button class="btn btn-primary">Save news</button>
                        </div>
                    </form>
                    @if($newsItem->galleryImages->isNotEmpty())
                        <div class="mt-5 border-t border-slate-100 pt-4">
                            <h3 class="mb-3 text-sm font-semibold text-navy-950">Current project gallery</h3>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                @foreach($newsItem->galleryImages as $galleryImage)
                                    <div class="space-y-2">
                                        <img class="h-28 w-full rounded-lg object-cover" src="{{ $galleryImage->image_url }}" alt="Project gallery image">
                                        <form method="POST" action="{{ route('admin.latest-news.gallery.destroy', [$newsItem, $galleryImage]) }}" onsubmit="return confirm('Remove this gallery image?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger w-full">Remove image</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('admin.latest-news.destroy', $newsItem) }}" class="mt-3 flex justify-end" onsubmit="return confirm('Remove this news item from the homepage?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger">Remove news</button>
                    </form>
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection
