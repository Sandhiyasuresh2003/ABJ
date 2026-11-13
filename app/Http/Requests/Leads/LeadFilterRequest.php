<?php

namespace App\Http\Requests\Leads;

use App\Models\Employee;
use App\Models\Lead;
use App\Services\Leads\LeadData;
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
            'mobile' => ['nullable', 'string', 'max:25', 'regex:/^(?=.*[0-9])[0-9+().\s-]+$/'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('employees', 'id'), function ($attribute, $value, $fail) {
                if ($this->user()->role === 'employee' && ! Employee::whereKey($value)->where('email', $this->user()->email)->where('employment_status', 'Active')->exists()) {
                    $fail('You may only filter your own leads.');
                }
            }],
            'project_category' => ['nullable', Rule::in(LeadData::PROJECT_CATEGORIES)],
            'text' => ['nullable', 'string', 'max:1000'],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ];
    }
}
