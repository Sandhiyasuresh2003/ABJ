<?php

namespace App\Services\Leads;

use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use ZipArchive;

class LeadWorkbook
{
    public const HEADERS = ['Lead Name', 'Mobile Number', 'Location', 'Requirement', 'Assigned Employee'];

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }

    public function inspect(string $path, string $extension): array
    {
        if ($extension === 'csv') {
            return [['worksheetName' => 'CSV', 'totalRows' => count($this->readCsv($path)), 'totalColumns' => config('leads.max_columns')]];
        }
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            $this->invalid('The XLSX file is not a valid workbook.');
        }
        try {
            if ($zip->numFiles > 2000) {
                $this->invalid('The workbook contains too many entries.');
            }
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $name = $entry['name'];
                $total += $entry['size'];
                if ($total > config('leads.max_uncompressed_bytes')) {
                    $this->invalid('The expanded workbook exceeds the size limit.');
                }
                if (preg_match('~(?:^/|\\\\|\.\./|vbaProject|externalLinks/|embeddings/|connections\.xml|queryTables/)~i', $name)) {
                    $this->invalid('Macros, embedded objects, external data and unsafe archive entries are not accepted.');
                }
                if (preg_match('/\.(xml|rels)$/i', $name)) {
                    $xml = $zip->getFromIndex($i);
                    if ($xml === false || preg_match('/<!DOCTYPE|<!ENTITY/i', $xml)) {
                        $this->invalid('Unsafe XML content is not accepted.');
                    }
                }
            }
            if ($zip->locateName('xl/workbook.xml') === false || str_contains(strtolower($zip->getFromName('[Content_Types].xml') ?: ''), 'macroenabled')) {
                $this->invalid('Upload a standard XLSX workbook without macros.');
            }
        } finally {
            $zip->close();
        }
        try {
            $info = (new Xlsx)->listWorksheetInfo($path);
        } catch (\Throwable) {
            $this->invalid('The workbook could not be read. Save it as a standard XLSX and retry.');
        }
        if (! $info || count($info) > 20) {
            $this->invalid('A workbook must contain between 1 and 20 worksheets.');
        }

        return $info;
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (! $handle) {
            $this->invalid('The upload could not be read.');
        }
        $rows = [];
        try {
            while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
                if (count($rows) >= config('leads.max_rows') + 1) {
                    $this->invalid('The file exceeds the row limit.');
                }
                if (count($row) > config('leads.max_columns')) {
                    $this->invalid('The file has too many columns.');
                }
                foreach ($row as $value) {
                    if ($value !== null && (! mb_check_encoding($value, 'UTF-8') || str_contains($value, "\0"))) {
                        $this->invalid('CSV files must contain UTF-8 text.');
                    }
                }
                $rows[] = array_map(fn ($value) => (string) $value, $row);
            }
        } finally {
            fclose($handle);
        }
        if ($rows) {
            $rows[0][0] = preg_replace('/^\xEF\xBB\xBF/', '', $rows[0][0] ?? '');
        }

        return $rows;
    }

    public function rows(string $path, string $extension, int $sheet): array
    {
        $info = $this->inspect($path, $extension);
        if (! isset($info[$sheet])) {
            $this->invalid('Select an available worksheet.');
        }
        if ($info[$sheet]['totalRows'] > config('leads.max_rows') + 1 || $info[$sheet]['totalColumns'] > config('leads.max_columns')) {
            $this->invalid('The selected sheet exceeds the row or column limit. Split it into smaller sheets.');
        }
        if ($extension === 'csv') {
            return $this->readCsv($path);
        }
        $reader = new Xlsx;
        $reader->setReadDataOnly(true)->setReadEmptyCells(false)->setLoadSheetsOnly($info[$sheet]['worksheetName']);
        $reader->setReadFilter(new class implements IReadFilter
        {
            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return $row <= config('leads.max_rows') + 1 && Coordinate::columnIndexFromString($columnAddress) <= config('leads.max_columns');
            }
        });
        try {
            $book = $reader->load($path);
            try {
                $worksheet = $book->getSheet(0);
                $rows = [];
                for ($row = 1; $row <= $info[$sheet]['totalRows']; $row++) {
                    $values = [];
                    for ($column = 1; $column <= $info[$sheet]['totalColumns']; $column++) {
                        $cell = $worksheet->getCell([$column, $row]);
                        // Never call getCalculatedValue() or toArray() with calculation enabled.
                        $value = $cell->getValue();
                        if ($value instanceof RichText) {
                            $value = $value->getPlainText();
                        }
                        $values[] = $cell->getDataType() === DataType::TYPE_FORMULA ? '=FORMULA_NOT_ALLOWED' : (string) ($value ?? '');
                    }
                    $rows[] = $values;
                }

                return $rows;
            } finally {
                $book->disconnectWorksheets();
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable) {
            $this->invalid('The selected worksheet could not be read safely.');
        }
    }

    public function suggestions(array $headers): array
    {
        $aliases = [
            'lead_name' => ['lead name', 'customer name', 'client name'],
            'mobile_number' => ['mobile number', 'mobile no', 'mobile', 'phone', 'phone number'],
            'location' => ['location'], 'requirement' => ['requirement', 'requirements'],
            'assigned_to' => ['assigned employee', 'telecaller', 'executive'],
        ];
        $suggestions = [];
        foreach ($aliases as $field => $names) {
            foreach ($headers as $index => $header) {
                if (in_array(strtolower(trim($header)), $names, true)) {
                    $suggestions[$field] = $index;
                    break;
                }
            }
        }

        return $suggestions;
    }
}
