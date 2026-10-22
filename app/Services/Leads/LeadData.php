<?php

namespace App\Services\Leads;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class LeadData
{
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
        ];
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
