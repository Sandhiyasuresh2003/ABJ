<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AboutUsPageSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class AboutUsPageController extends Controller
{
    public function edit(): View
    {
        return view('admin.about-us.edit', [
            'settings' => AboutUsPageSetting::query()->findOrFail(1),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'heading' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:2000'],
            'landing_image_one' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
            'landing_image_two' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $settings = AboutUsPageSetting::query()->findOrFail(1);
        $newPaths = [];
        $oldPaths = [];
        $updates = [
            'label' => $validated['label'],
            'heading' => $validated['heading'],
            'description' => $validated['description'],
        ];

        foreach (['landing_image_one', 'landing_image_two'] as $field) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $oldPaths[$field] = $settings->{$field};
            $path = $request->file($field)->storePublicly('about-us', 'public');
            if ($path === false) {
                foreach ($newPaths as $newPath) {
                    Storage::disk('public')->delete($newPath);
                }

                return back()->withInput($request->only('label', 'heading', 'description'))
                    ->withErrors([$field => 'The image could not be saved. Please try again.']);
            }

            $newPaths[$field] = $path;
            $updates[$field] = $path;
        }

        try {
            $settings->update($updates);
        } catch (Throwable $exception) {
            foreach ($newPaths as $newPath) {
                Storage::disk('public')->delete($newPath);
            }

            throw $exception;
        }

        foreach ($newPaths as $field => $newPath) {
            $oldPath = $oldPaths[$field];
            if ($oldPath && $oldPath !== $newPath) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        return redirect()->route('admin.about-us.edit')->with('success', 'About Us content saved.');
    }
}
