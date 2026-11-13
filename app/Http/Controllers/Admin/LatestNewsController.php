<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LatestNews;
use App\Models\LatestNewsGalleryImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class LatestNewsController extends Controller
{
    public function index(): View
    {
        return view('admin.latest-news.index', [
            'newsItems' => LatestNews::query()
                ->with('galleryImages')
                ->orderByDesc('published_at')
                ->orderBy('position')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:2000'],
            'published_at' => ['required', 'date'],
            'project_name' => ['required', 'string', 'max:150'],
            'project_location' => ['required', 'string', 'max:255'],
            'project_about' => ['required', 'string', 'max:10000'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
            'gallery_images' => ['nullable', 'array', 'max:20'],
            'gallery_images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $stagedPaths = [];
        $imagePaths = $this->storeImages([$request->file('image')], $stagedPaths, 'latest-news');
        if ($imagePaths === false) {
            return back()->withInput()->withErrors(['image' => 'The news image could not be saved. Please try again.']);
        }

        $galleryPaths = $this->storeImages($validated['gallery_images'] ?? [], $stagedPaths, 'latest-news/gallery');
        if ($galleryPaths === false) {
            return back()->withInput()->withErrors(['gallery_images' => 'A gallery image could not be saved. Please try again.']);
        }

        try {
            DB::transaction(function () use ($validated, $imagePaths, $galleryPaths): void {
                $newsItem = LatestNews::query()->create([
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'published_at' => $validated['published_at'],
                    'project_name' => $validated['project_name'],
                    'project_location' => $validated['project_location'],
                    'project_about' => $validated['project_about'],
                    'image_path' => $imagePaths[0],
                    'position' => ((int) LatestNews::query()->max('position')) + 1,
                ]);

                foreach ($galleryPaths as $position => $galleryPath) {
                    $newsItem->galleryImages()->create([
                        'image_path' => $galleryPath,
                        'position' => $position + 1,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            $this->discardPaths($stagedPaths);
            throw $exception;
        }

        return redirect()->route('admin.latest-news.index')->with('success', 'News item added.');
    }

    public function update(Request $request, LatestNews $latestNews): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:2000'],
            'published_at' => ['required', 'date'],
            'project_name' => ['required', 'string', 'max:150'],
            'project_location' => ['required', 'string', 'max:255'],
            'project_about' => ['required', 'string', 'max:10000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
            'gallery_images' => ['nullable', 'array', 'max:20'],
            'gallery_images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $oldPath = $latestNews->image_path;
        $stagedPaths = [];
        $imagePaths = [];
        if ($request->hasFile('image')) {
            $imagePaths = $this->storeImages([$request->file('image')], $stagedPaths, 'latest-news');
            if ($imagePaths === false) {
                return back()->withInput()->withErrors(['image' => 'The news image could not be saved. Please try again.']);
            }
        }

        $galleryPaths = $this->storeImages($validated['gallery_images'] ?? [], $stagedPaths, 'latest-news/gallery');
        if ($galleryPaths === false) {
            return back()->withInput()->withErrors(['gallery_images' => 'A gallery image could not be saved. Please try again.']);
        }
        $newPath = $imagePaths[0] ?? null;

        try {
            DB::transaction(function () use ($latestNews, $validated, $newPath, $oldPath, $galleryPaths): void {
                $latestNews->update([
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'published_at' => $validated['published_at'],
                    'project_name' => $validated['project_name'],
                    'project_location' => $validated['project_location'],
                    'project_about' => $validated['project_about'],
                    'image_path' => $newPath ?? $oldPath,
                ]);

                $position = (int) $latestNews->galleryImages()->max('position');
                foreach ($galleryPaths as $galleryPath) {
                    $latestNews->galleryImages()->create([
                        'image_path' => $galleryPath,
                        'position' => ++$position,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            $this->discardPaths($stagedPaths);
            throw $exception;
        }

        if ($newPath && str_starts_with($oldPath, 'latest-news/')) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->route('admin.latest-news.index')->with('success', 'News item updated.');
    }

    public function destroyGalleryImage(LatestNews $latestNews, LatestNewsGalleryImage $galleryImage): RedirectResponse
    {
        $galleryImage = $latestNews->galleryImages()->whereKey($galleryImage->getKey())->firstOrFail();
        $path = $galleryImage->image_path;
        $galleryImage->delete();
        Storage::disk('public')->delete($path);

        return redirect()->route('admin.latest-news.index')->with('success', 'Gallery image removed.');
    }

    public function destroy(LatestNews $latestNews): RedirectResponse
    {
        $path = $latestNews->image_path;
        $galleryPaths = $latestNews->galleryImages()->pluck('image_path')->all();
        $latestNews->delete();

        if (str_starts_with($path, 'latest-news/')) {
            Storage::disk('public')->delete($path);
        }
        foreach ($galleryPaths as $galleryPath) {
            Storage::disk('public')->delete($galleryPath);
        }

        return redirect()->route('admin.latest-news.index')->with('success', 'News item removed.');
    }

    private function storeImages(array $files, array &$stagedPaths, string $directory): array|false
    {
        $paths = [];
        foreach ($files as $file) {
            try {
                $path = $file->storePublicly($directory, 'public');
            } catch (Throwable $exception) {
                $this->discardPaths($stagedPaths);
                throw $exception;
            }

            if ($path === false) {
                $this->discardPaths($stagedPaths);

                return false;
            }

            $stagedPaths[] = $path;
            $paths[] = $path;
        }

        return $paths;
    }

    private function discardPaths(array $paths): void
    {
        foreach ($paths as $path) {
            Storage::disk('public')->delete($path);
        }
    }
}
