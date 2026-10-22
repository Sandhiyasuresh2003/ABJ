<?php

namespace App\Services\Leads;

use App\Models\Lead;
use App\Models\LeadImport;
use App\Models\User;
use App\Policies\LeadPolicy;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LeadImporter
{
    public function preview(User $user, array $rows, array $mapping, array $defaults): array
    {
        $headers = array_shift($rows) ?? [];
        $seen = [];
        $result = [];
        foreach ($rows as $index => $values) {
            if (! array_filter($values, fn ($value) => trim($value) !== '')) {
                continue;
            }
            $data = [];
            foreach (['lead_name', 'mobile_number', 'location', 'requirement', 'assigned_to'] as $field) {
                $column = $mapping[$field] ?? null;
                $data[$field] = trim((string) ($column !== null && $column !== '' ? ($values[(int) $column] ?? '') : ($defaults[$field] ?? '')));
            }
            $data['mobile_number'] = LeadData::normalizePhone($data['mobile_number']);
            $validator = Validator::make($data, LeadData::rules());
            $errors = $validator->errors()->all();
            if (count($values) > count($headers) && array_filter(array_slice($values, count($headers)), fn ($value) => trim($value) !== '')) {
                $errors[] = 'The row has populated columns without a header.';
            }
            if (array_filter($values, fn ($value) => preg_match('/^[\s\x00-\x1f]*[=+@-]/u', $value) && ! preg_match('/^\+?[0-9\s().-]+$/', trim($value)))) {
                $errors[] = 'Formula-like values are not allowed. Replace formulas with plain values.';
            }
            if (array_filter($values, fn ($value) => mb_strlen($value) > 3000)) {
                $errors[] = 'A cell exceeds 3,000 characters.';
            }
            $assignment = $data['assigned_to'];
            $data['assigned_to'] = null;
            if ($assignment !== '') {
                if (! (new LeadPolicy)->permitted($user, 'assign')) {
                    $errors[] = 'You cannot assign employees during import.';
                } else {
                    $matches = LeadData::eligibleEmployees()->where(fn ($q) => $q->where('employee_code', $assignment)->orWhere('email', $assignment)->orWhere('name', $assignment))->limit(2)->get();
                    if ($matches->count() !== 1) {
                        $errors[] = 'Assigned employee must uniquely match an active employee code, email, or name.';
                    } else {
                        $data['assigned_to'] = $matches->first()->id;
                    }
                }
            }
            $duplicate = isset($seen[$data['mobile_number']]) ? 'Duplicate mobile within this worksheet.' : null;
            if (! $duplicate && Lead::where('normalized_mobile', $data['mobile_number'])->exists()) {
                $duplicate = 'Mobile already exists in leads.';
            }
            // Only a valid row reserves a mobile; a malformed earlier row must not hide a later valid row.
            if (! $errors) {
                $seen[$data['mobile_number']] = true;
            }
            $source = [];
            foreach ($values as $column => $value) {
                if ($value !== '') {
                    $label = ($headers[$column] ?? '') ?: 'Column '.($column + 1);
                    if (array_key_exists($label, $source)) {
                        $label .= ' (column '.($column + 1).')';
                    }
                    $source[$label] = mb_substr($value, 0, 3000);
                }
            }
            $data['source_data'] = $source;
            $result[] = ['row' => $index + 2, 'state' => $errors ? 'invalid' : ($duplicate ? 'duplicate' : 'valid'), 'errors' => $errors, 'duplicate' => $duplicate, 'data' => $data];
        }
        if (! $result) {
            throw ValidationException::withMessages(['file' => 'No lead rows were found below the header row.']);
        }

        return $result;
    }

    public function confirm(User $user, LeadImport $import): LeadImport
    {
        Gate::forUser($user)->authorize('import', Lead::class);

        return DB::transaction(function () use ($user, $import) {
            $locked = LeadImport::whereKey($import->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->uploaded_by === $user->id, 403);
            if ($locked->status === 'completed') {
                return $locked;
            }
            abort_unless(in_array($locked->status, ['preview', 'failed'], true) && $locked->payload && $locked->expires_at->isFuture(), 422, 'This preview has expired or is not ready. Upload the file again.');
            $rows = $locked->payload;
            $imported = $duplicates = $failed = 0;
            foreach ($rows as $row) {
                if ($row['state'] === 'invalid') {
                    $failed++;

                    continue;
                }
                if ($row['state'] === 'duplicate') {
                    $duplicates++;

                    continue;
                }
                $data = $row['data'];
                $errors = Validator::make($data, LeadData::rules())->errors();
                if ($data['assigned_to'] && (! (new LeadPolicy)->permitted($user, 'assign') || ! LeadData::eligibleEmployees()->whereKey($data['assigned_to'])->lockForUpdate()->exists())) {
                    $failed++;

                    continue;
                }
                if ($errors->isNotEmpty()) {
                    $failed++;

                    continue;
                }
                try {
                    // Nested transaction gives a savepoint; a duplicate cannot poison the batch transaction.
                    (new LeadActions)->create($user, $data);
                    $imported++;
                } catch (UniqueConstraintViolationException $exception) {
                    if (! Lead::where('normalized_mobile', $data['mobile_number'])->lockForUpdate()->exists()) {
                        throw $exception;
                    }
                    $duplicates++;
                }
            }
            $locked->update(['status' => 'completed', 'imported_rows' => $imported, 'duplicate_rows' => $duplicates, 'failed_rows' => $failed, 'payload' => null]);
            LeadActions::audit($user, 'imported', null, ['import_id' => $locked->id, 'imported' => $imported, 'duplicates' => $duplicates, 'failed' => $failed]);

            return $locked;
        }, 3);
    }
}
