<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientTestimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class ClientTestimonialController extends Controller
{
    public function index(): View
    {
        return view('admin.client-feedback.index', [
            'testimonials' => ClientTestimonial::query()->orderBy('position')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'role' => ['required', 'string', 'max:100'],
            'feedback' => ['required', 'string', 'max:2000'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $path = $request->file('image')->storePublicly('client-feedback', 'public');
        if ($path === false) {
            return back()->withInput()->withErrors(['image' => 'The client photo could not be saved. Please try again.']);
        }

        try {
            ClientTestimonial::query()->create([
                'name' => $validated['name'],
                'role' => $validated['role'],
                'feedback' => $validated['feedback'],
                'image_path' => $path,
                'position' => ((int) ClientTestimonial::query()->max('position')) + 1,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

        return redirect()->route('admin.client-feedback.index')->with('success', 'Client feedback added.');
    }

    public function update(Request $request, ClientTestimonial $testimonial): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'role' => ['required', 'string', 'max:100'],
            'feedback' => ['required', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $oldPath = $testimonial->image_path;
        $newPath = null;
        if ($request->hasFile('image')) {
            $newPath = $request->file('image')->storePublicly('client-feedback', 'public');
            if ($newPath === false) {
                return back()->withInput()->withErrors(['image' => 'The client photo could not be saved. Please try again.']);
            }
        }

        try {
            $testimonial->update([
                'name' => $validated['name'],
                'role' => $validated['role'],
                'feedback' => $validated['feedback'],
                'image_path' => $newPath ?? $oldPath,
            ]);
        } catch (Throwable $exception) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }

            throw $exception;
        }

        if ($newPath && str_starts_with($oldPath, 'client-feedback/')) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->route('admin.client-feedback.index')->with('success', 'Client feedback updated.');
    }

    public function destroy(ClientTestimonial $testimonial): RedirectResponse
    {
        $path = $testimonial->image_path;
        $testimonial->delete();

        if (str_starts_with($path, 'client-feedback/')) {
            Storage::disk('public')->delete($path);
        }

        return redirect()->route('admin.client-feedback.index')->with('success', 'Client feedback removed.');
    }
}
