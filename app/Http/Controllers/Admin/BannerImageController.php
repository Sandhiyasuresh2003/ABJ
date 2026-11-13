<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BannerImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class BannerImageController extends Controller
{
    public function index(): View
    {
        return view('admin.banners.index', [
            'banners' => BannerImage::query()->orderBy('position')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $storedPaths = [];
        foreach ($validated['images'] as $image) {
            try {
                $path = $image->storePublicly('banners', 'public');
            } catch (Throwable $exception) {
                foreach ($storedPaths as $storedPath) {
                    Storage::disk('public')->delete($storedPath);
                }

                throw $exception;
            }

            if ($path === false) {
                foreach ($storedPaths as $storedPath) {
                    Storage::disk('public')->delete($storedPath);
                }

                return back()->withInput()->withErrors(['images' => 'A banner image could not be saved. Please try again.']);
            }

            $storedPaths[] = $path;
        }

        try {
            DB::transaction(function () use ($storedPaths): void {
                $position = (int) BannerImage::query()->max('position');
                foreach ($storedPaths as $path) {
                    BannerImage::query()->create([
                        'image_path' => $path,
                        'position' => ++$position,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        return redirect()->route('admin.banners.index')->with('success', 'Banner images saved.');
    }

    public function destroy(BannerImage $banner): RedirectResponse
    {
        $path = $banner->image_path;
        $banner->delete();
        Storage::disk('public')->delete($path);

        return redirect()->route('admin.banners.index')->with('success', 'Banner image removed.');
    }
}
