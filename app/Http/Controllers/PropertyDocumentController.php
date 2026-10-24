<?php

namespace App\Http\Controllers;

use App\Models\PropertyDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PropertyDocumentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['type' => ['nullable', Rule::in(array_keys(PropertyDocument::TYPES))]]);
        $type = $filters['type'] ?? null;
        $documents = PropertyDocument::with('uploader')
            ->when($type, fn ($query) => $query->where('type', $type))
            ->latest('id')->paginate(15)->withQueryString();

        return view('documents.index', [
            'documents' => $documents,
            'types' => PropertyDocument::TYPES,
            'selectedType' => $type,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(array_keys(PropertyDocument::TYPES))],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $file = $request->file('document');
        $path = $file->store('property-documents', 'local');
        if ($path === false) {
            return back()->withInput($request->only('title', 'type'))
                ->withErrors(['document' => 'The document could not be saved. Please try again.']);
        }

        try {
            PropertyDocument::create([
                'title' => $validated['title'],
                'type' => $validated['type'],
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
                'uploaded_by' => $request->user()->id,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return redirect()->route('documents.index')->with('success', 'Document uploaded successfully.');
    }

    public function download(PropertyDocument $document): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($document->path), 404, 'Document file not found.');

        return Storage::disk('local')->download($document->path, $document->original_name, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
