<?php

namespace App\Http\Requests\Leads;

use App\Models\Employee;
use App\Models\Lead;
use App\Models\LeadFollowup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Lead::class);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'status' => ['nullable', Rule::in(['Active', 'Inactive'])],
            'assigned_to' => ['nullable', 'integer', Rule::exists('employees', 'id'), function ($attribute, $value, $fail) {
                if ($this->user()->role === 'employee' && ! Employee::whereKey($value)->where('email', $this->user()->email)->where('employment_status', 'Active')->exists()) {
                    $fail('You may only filter your own leads.');
                }
            }],
            'location' => ['nullable', 'string', 'max:150'],
            'outcome' => ['nullable', Rule::in(LeadFollowup::OUTCOMES)],
            'next_followup' => ['nullable', 'date_format:Y-m-d'],
            'sort' => ['nullable', Rule::in(['lead_name', 'location', 'status', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ];
    }
}
