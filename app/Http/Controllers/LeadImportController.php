<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Lead;
use App\Models\LeadImport;
use App\Services\Leads\LeadActions;
use App\Services\Leads\LeadData;
use App\Services\Leads\LeadImporter;
use App\Services\Leads\LeadWorkbook;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LeadImportController extends Controller
{
    public function index()
    {
        Gate::authorize('history', Lead::class);
        LeadActions::audit(request()->user(), 'import_history_viewed');

        return view('leads.imports.index', ['imports' => LeadImport::with('uploader')->latest()->paginate(20)]);
    }

    public function create()
    {
        Gate::authorize('import', Lead::class);

        return view('leads.imports.create');
    }

    public function template()
    {
        Gate::authorize('import', Lead::class);

        return response()->streamDownload(function () {
            $book = new Spreadsheet;
            try {
                $sheet = $book->getActiveSheet()->setTitle('Leads');
                $sheet->fromArray(LeadWorkbook::HEADERS, null, 'A1');
                $sheet->getStyle('A1:E1')->getFont()->setBold(true);
                $sheet->getStyle('A1:E1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFDCFCE7');
                foreach (['A' => 25, 'B' => 24, 'C' => 25, 'D' => 45, 'E' => 28] as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }
                $sheet->getStyle('B2:B5001')->getNumberFormat()->setFormatCode('@');
                $sheet->freezePane('A2');
                (new Xlsx($book))->setPreCalculateFormulas(false)->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, 'abj-leads-template.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function store(Request $request, LeadWorkbook $workbook)
    {
        Gate::authorize('import', Lead::class);
        $request->validate(['file' => ['required', 'file', 'max:'.config('leads.max_upload_kb')]]);
        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $allowed = $extension === 'xlsx'
            ? ['application/zip', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/octet-stream']
            : ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'];
        if (! in_array($extension, ['xlsx', 'csv'], true) || ! in_array($file->getMimeType(), $allowed, true)) {
            throw ValidationException::withMessages(['file' => 'Upload a valid .xlsx or UTF-8 .csv file. Legacy .xls files are not supported.']);
        }
        $workbook->inspect($file->getPathname(), $extension);
        $path = $file->storeAs('lead-imports', bin2hex(random_bytes(24)).'.'.$extension, 'local');
        abort_unless($path, 500, 'The upload could not be saved. Try again.');
        try {
            $import = DB::transaction(function () use ($request, $file, $path) {
                $import = LeadImport::create(['uploaded_by' => $request->user()->id, 'original_filename' => mb_substr(basename(str_replace('\\', '/', $file->getClientOriginalName())), 0, 255), 'path' => $path, 'expires_at' => now()->addHours(config('leads.preview_hours'))]);
                LeadActions::audit($request->user(), 'import_uploaded', null, ['import_id' => $import->id]);

                return $import;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return redirect()->route('leads.imports.show', $import);
    }

    private function authorizeImport(Request $request, LeadImport $import): void
    {
        Gate::authorize('import', Lead::class);
        abort_unless($import->uploaded_by === $request->user()->id, 403);
        abort_unless($import->expires_at->isFuture(), 422, 'The upload has expired. Upload a new copy.');
    }

    public function show(Request $request, LeadImport $import, LeadWorkbook $workbook)
    {
        if ($import->status === 'completed' || $import->status === 'expired') {
            Gate::authorize('history', Lead::class);

            return view('leads.imports.result', ['import' => $import->load('uploader')]);
        }
        $this->authorizeImport($request, $import);
        if (in_array($import->status, ['preview', 'failed'], true) && $import->payload) {
            $request->validate(['page' => ['nullable', 'integer', 'min:1', 'max:1000000']]);
            $rows = $import->payload;
            $page = (int) $request->input('page', 1);
            $preview = new LengthAwarePaginator(array_slice($rows, ($page - 1) * 50, 50), count($rows), 50, $page, ['path' => $request->url()]);
            $employeeLabels = Employee::whereIn('id', collect($preview->items())->pluck('data.assigned_to')->filter()->unique())
                ->get()->mapWithKeys(fn ($employee) => [$employee->id => $employee->employee_code.' — '.$employee->name]);

            return view('leads.imports.preview', compact('import', 'preview', 'employeeLabels'));
        }
        $request->validate(['sheet' => ['nullable', 'integer', 'min:0', 'max:19']]);
        abort_unless($import->path && Storage::disk('local')->exists($import->path), 422, 'The source upload is unavailable. Upload a new copy.');
        $path = Storage::disk('local')->path($import->path);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $sheets = $workbook->inspect($path, $extension);
        $sheet = (int) $request->input('sheet', 0);
        $rows = $workbook->rows($path, $extension, $sheet);
        $headers = $rows[0] ?? [];

        return view('leads.imports.map', ['import' => $import, 'sheets' => $sheets, 'sheet' => $sheet, 'headers' => $headers, 'suggestions' => $workbook->suggestions($headers)]);
    }

    public function preview(Request $request, LeadImport $import, LeadWorkbook $workbook, LeadImporter $importer)
    {
        $this->authorizeImport($request, $import);
        $request->validate(['sheet' => ['required', 'integer', 'between:0,19'], 'mapping' => ['required', 'array:lead_name,mobile_number,location,requirement,assigned_to'], 'defaults' => ['nullable', 'array:location,requirement'], 'defaults.location' => ['nullable', 'string', 'max:150'], 'defaults.requirement' => ['nullable', 'string', 'max:1000']]);
        DB::transaction(function () use ($request, $import, $workbook, $importer) {
            $locked = LeadImport::whereKey($import->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'uploaded' && $locked->path && Storage::disk('local')->exists($locked->path), 422, 'Upload a new file to prepare another preview.');
            $path = Storage::disk('local')->path($locked->path);
            $rows = $workbook->rows($path, pathinfo($path, PATHINFO_EXTENSION), (int) $request->input('sheet'));
            $columns = array_keys(array_filter($rows[0] ?? [], fn ($header) => trim($header) !== ''));
            foreach (['lead_name', 'mobile_number', 'location', 'requirement', 'assigned_to'] as $field) {
                $request->validate(['mapping.'.$field => [$field === 'lead_name' || $field === 'mobile_number' ? 'required' : 'nullable', 'integer', Rule::in($columns)]]);
                if (in_array($field, ['location', 'requirement'], true) && $request->input('mapping.'.$field) === null) {
                    $request->validate(['defaults.'.$field => LeadData::rules()[$field]]);
                }
            }
            $payload = $importer->preview($request->user(), $rows, $request->input('mapping'), $request->input('defaults', []));
            $locked->update(['status' => 'preview', 'total_rows' => count($payload), 'duplicate_rows' => count(array_filter($payload, fn ($row) => $row['state'] === 'duplicate')), 'failed_rows' => count(array_filter($payload, fn ($row) => $row['state'] === 'invalid')), 'payload' => $payload]);
            LeadActions::audit($request->user(), 'import_previewed', null, ['import_id' => $locked->id]);
        });

        return redirect()->route('leads.imports.show', $import);
    }

    public function confirm(Request $request, LeadImport $import, LeadImporter $importer)
    {
        $this->authorizeImport($request, $import);
        $request->validate(['confirm' => ['accepted']]);
        try {
            $result = $importer->confirm($request->user(), $import);
        } catch (ValidationException|HttpException $exception) {
            throw $exception;
        } catch (\Throwable) {
            LeadImport::whereKey($import->id)->whereIn('status', ['preview', 'failed'])->update(['status' => 'failed']);

            return back()->withErrors(['file' => 'The import could not be completed. No leads from this attempt were saved. Retry this confirmation or upload a corrected file.']);
        }
        if ($result->path) {
            if (Storage::disk('local')->delete($result->path)) {
                $result->update(['path' => null]);
            }
        }

        return redirect()->route('leads.imports.show', $result)->with('success', 'Import completed.');
    }
}
