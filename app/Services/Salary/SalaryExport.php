<?php

namespace App\Services\Salary;

use App\Models\SalaryStatement;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SalaryExport
{
    public function download(SalaryStatement $statement, string $format)
    {
        $name = 'salary-'.$statement->employee_id.'-'.$statement->salary_month->format('Y-m');
        if ($format === 'pdf') {
            $pdf = new Dompdf(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans', 'tempDir' => storage_path('framework/cache')]);
            $pdf->loadHtml(view('salary.pdf', compact('statement'))->render());
            $pdf->setPaper('A4');
            $pdf->render();
            $pdf->getCanvas()->page_text(490, 817, 'Page {PAGE_NUM} of {PAGE_COUNT}', $pdf->getFontMetrics()->getFont('DejaVu Sans'), 8, [0.2, 0.3, 0.25]);

            return response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$name.'.pdf"', 'Cache-Control' => 'private, no-store']);
        }

        return response()->streamDownload(function () use ($statement) {
            $book = new Spreadsheet;
            $sheet = $book->getActiveSheet();
            $sheet->setTitle('Salary statement');
            $s = $statement->snapshot;
            $rows = [
                ['ABJ Builders - Monthly Salary Statement'],
                ['Employee', $s['employee']['name']], ['Employee ID', $s['employee']['employee_code']],
                ['Department', $s['employee']['department']], ['Designation', $s['employee']['designation']],
                ['Joining date', substr($s['employee']['joining_date'], 0, 10)],
                ['Salary month', $s['month']], ['Status', $statement->status], ['Revision', $statement->revision],
                ['Payment status', $statement->payment_status], ['Payment date', $statement->payment_date?->toDateString() ?? ''],
                ['Payment reference', $statement->payment_reference ?? ''],
            ];
            foreach ($this->summary($s) as $label => $value) {
                $rows[] = [$label, $value];
            }
            foreach ($s['deductions'] as $label => $value) {
                $rows[] = [ucwords(str_replace('_', ' ', $label)).' deduction (INR)', $value / 100];
            }
            $rows[] = ['Adjustment reason', $statement->adjustment_reason ?? ''];
            $rows[] = [];
            $rows[] = ['Date', 'Status', 'Check in', 'Check out', 'Sunday', 'Late', 'Approved permission', 'Deduction', 'Days deducted'];
            foreach ($s['days'] as $day) {
                $rows[] = array_values($day);
            }
            $rows[] = [];
            $rows[] = ['Emergency permission history', 'Start', 'End', 'Reason', 'Status', 'Reviewed by'];
            foreach ($s['permissions'] as $p) {
                $rows[] = [$p['permission_date'], $p['start_time'], $p['end_time'], $p['reason'], $p['status'], $p['reviewed_by']];
            }
            $rows[] = [];
            $rows[] = ['Leave history', 'From', 'To', 'Reason', 'Status', 'Reviewed by'];
            foreach ($s['leaves'] as $l) {
                $rows[] = [$l['leave_type'], $l['start_date'], $l['end_date'], $l['reason'], $l['status'], $l['reviewed_by']];
            }
            foreach ($rows as $i => $row) {
                foreach ($row as $j => $value) {
                    // Untrusted names, reasons and references must never become spreadsheet formulas.
                    $sheet->setCellValueExplicit([$j + 1, $i + 1], is_bool($value) ? ($value ? 'Yes' : 'No') : ($value ?? ''), is_int($value) || is_float($value) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING);
                }
            }
            $sheet->getStyle('A1:I1')->getFont()->setBold(true)->setSize(14);
            foreach (range('A', 'I') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            $sheet->freezePane('A2');
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, $name.'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Cache-Control' => 'private, no-store']);
    }

    public function summary(array $s): array
    {
        return [
            'Monthly basic salary (INR)' => $s['basic_salary'] / 100,
            'Earned basic salary (INR)' => $s['earned_basic'] / 100,
            'Sunday incentive (INR)' => $s['sunday_incentive'] / 100,
            'Daily salary (INR)' => $s['daily_salary'], 'Per-minute salary (INR)' => $s['per_minute_salary'],
            'Salary divisor' => $s['divisor'], 'Working days' => $s['working_days'],
            'Present days' => $s['present_days'], 'Sunday attendance' => $s['sunday_attendance'].' / '.$s['sundays'],
            'Late arrivals' => $s['late_arrivals'], 'Permission allowance (minutes)' => $s['permission_allowance'],
            'Permission used (minutes)' => $s['permission_used'], 'Permission balance (minutes)' => $s['permission_balance'],
            'CL allowance ('.$s['configuration']['cl_period'].')' => $s['cl_allowance'], 'Prior CL used' => $s['cl_prior_used'],
            'CL used this month' => $s['cl_used'], 'CL balance' => $s['cl_balance'],
            'Week off days' => $s['week_off_days'], 'Absent days' => $s['absent_days'], 'Missing attendance' => $s['missing_days'],
            'Total deductions (INR)' => $s['total_deductions'] / 100, 'Final payable salary (INR)' => $s['payable_salary'] / 100,
        ];
    }
}
