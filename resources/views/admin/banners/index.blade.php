@extends('layouts.app')
@section('title', 'Homepage Banners')
@section('heading', 'Homepage Banners')
@section('breadcrumb', 'Homepage Banners')
@section('content')
<section class="page-intro">
    <h2 class="text-2xl font-bold">Homepage banner carousel</h2>
    <p class="mt-1 text-sm">Upload up to 10 banner images at a time. Images are shown in upload order and rotate automatically on the public homepage.</p>
</section>

<form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-soft sm:p-7">
    @csrf
    <div>
        <label for="banner-images" class="mb-1.5 block text-sm font-semibold">Add banner images</label>
        <input id="banner-images" class="field" type="file" name="images[]" accept=".jpg,.jpeg,.png,.webp" multiple required>
        <p class="mt-1 text-xs text-slate-500">Select multiple JPG, PNG or WebP images. Maximum 5 MB per image and 10 images per upload.</p>
    </div>
    <div class="flex justify-end">
        <button class="btn btn-primary">Upload banners</button>
    </div>
</form>

<section class="space-y-4">
    <div>
        <h2 class="text-xl font-bold text-navy-950">Current banners</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $banners->count() }} {{ \Illuminate\Support\Str::plural('image', $banners->count()) }} in the homepage carousel.</p>
    </div>
    @if($banners->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">No custom banners uploaded. The homepage will show its default banner.</div>
    @else
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($banners as $banner)
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft">
                    <img class="h-52 w-full object-cover" src="{{ asset('storage/'.$banner->image_path) }}" alt="Homepage banner {{ $loop->iteration }}">
                    <div class="flex items-center justify-between gap-3 p-4">
                        <p class="text-sm font-semibold text-slate-700">Banner {{ $loop->iteration }}</p>
                        <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" onsubmit="return confirm('Remove this banner from the homepage?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger">Remove</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>
@endsection
