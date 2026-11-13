<?php

namespace App\Services\Leads;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class LeadData
{
    public const PROJECT_CATEGORIES = ['Plots', 'Agri'];

    public static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s().-]+/u', '', trim($phone));
        $code = config('leads.country_code');
        if (str_starts_with($phone, '+'.$code)) {
            $phone = substr($phone, strlen($code) + 1);
        } elseif (strlen($phone) === 10 + strlen($code) && str_starts_with($phone, $code)) {
            $phone = substr($phone, strlen($code));
        } elseif (strlen($phone) === 11 && str_starts_with($phone, '0')) {
            $phone = substr($phone, 1);
        }

        return $phone;
    }

    public static function rules(): array
    {
        return [
            'lead_name' => ['required', 'string', 'max:150'],
            'mobile_number' => ['required', 'string', 'max:25', 'regex:'.config('leads.phone_pattern')],
            'location' => ['required', 'string', 'max:150'],
            'requirement' => ['required', 'string', 'max:1000'],
            'project_category' => ['required', 'string', 'in:Plots,Agri'],
        ];
    }

    public static function datedRequirementEntries(string $requirement): array
    {
        $datePattern = '/(?<![\pL\pN])(?:\d{1,2}[.\/-]\d{1,2}(?:[.\/-]\d{2,4})?|\d{1,2}\s+(?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:tember)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?)\.?(?:\s+\d{2,4})?|(?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:tember)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?)\.?\s+\d{1,2}(?:,?\s+\d{2,4})?)(?![\pL\pN])/iu';
        preg_match_all($datePattern, $requirement, $matches, PREG_OFFSET_CAPTURE);

        if (! $matches[0]) {
            $content = trim($requirement);

            return $content === '' ? [] : [['date' => null, 'content' => $content]];
        }

        $entries = [];
        $firstDateOffset = $matches[0][0][1];
        $beforeDates = trim(substr($requirement, 0, $firstDateOffset), " \t\n\r\0\x0B/|;,-");
        if ($beforeDates !== '') {
            $entries[] = ['date' => null, 'content' => $beforeDates];
        }

        foreach ($matches[0] as $index => [$date, $offset]) {
            $contentStart = $offset + strlen($date);
            $nextDateOffset = $matches[0][$index + 1][1] ?? strlen($requirement);
            $content = trim(substr($requirement, $contentStart, $nextDateOffset - $contentStart), " \t\n\r\0\x0B/|;,-");
            $entries[] = ['date' => $date, 'content' => $content];
        }

        return $entries;
    }

    public static function eligibleEmployees(): Builder
    {
        return Employee::query()->where('employment_status', 'Active')->whereIn('email', User::query()->select('email')->where('role', 'employee'));
    }

    public static function assignmentRules(): array
    {
        return ['nullable', 'integer', function ($attribute, $value, $fail) {
            if (! self::eligibleEmployees()->whereKey($value)->exists()) {
                $fail('Select an active employee with an employee login.');
            }
        }];
    }

    public static function safeCsv(string $value): string
    {
        return preg_match('/^[\s\x00-\x1f]*[=+@-]/u', $value) ? "'".$value : $value;
    }
}
