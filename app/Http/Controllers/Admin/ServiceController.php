<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('admin.services.index', [
            'services' => Service::query()->orderBy('position')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:2000'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $path = $request->file('image')->storePublicly('services', 'public');
        if ($path === false) {
            return back()->withInput()->withErrors(['image' => 'The service image could not be saved. Please try again.']);
        }

        try {
            Service::query()->create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'image_path' => $path,
                'position' => ((int) Service::query()->max('position')) + 1,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

        return redirect()->route('admin.services.index')->with('success', 'Service added.');
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $oldPath = $service->image_path;
        $newPath = null;
        if ($request->hasFile('image')) {
            $newPath = $request->file('image')->storePublicly('services', 'public');
            if ($newPath === false) {
                return back()->withInput()->withErrors(['image' => 'The service image could not be saved. Please try again.']);
            }
        }

        try {
            $service->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'image_path' => $newPath ?? $oldPath,
            ]);
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }

            throw $exception;
        }

        if ($newPath && str_starts_with($oldPath, 'services/')) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->route('admin.services.index')->with('success', 'Service updated.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $path = $service->image_path;
        $service->delete();

        if (str_starts_with($path, 'services/')) {
            Storage::disk('public')->delete($path);
        }

        return redirect()->route('admin.services.index')->with('success', 'Service removed.');
    }
}
