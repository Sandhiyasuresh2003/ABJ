<?php

namespace App\Http\Requests\Leads;

use App\Models\Lead;
use App\Services\Leads\LeadData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('lead') ? $this->user()->can('update', $this->route('lead')) : $this->user()->can('create', Lead::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('mobile_number'))) {
            $this->merge(['mobile_number' => LeadData::normalizePhone($this->input('mobile_number'))]);
        }
    }

    public function rules(): array
    {
        if ($this->route('lead') && $this->user()->role === 'employee') {
            return [
                'location' => LeadData::rules()['location'], 'requirement' => LeadData::rules()['requirement'],
                'lead_name' => 'prohibited', 'mobile_number' => 'prohibited', 'assigned_to' => 'prohibited',
                'normalized_mobile' => 'prohibited', 'status' => 'prohibited', 'created_by' => 'prohibited', 'source_data' => 'prohibited',
            ];
        }
        $rules = LeadData::rules();
        $rules['mobile_number'][] = Rule::unique('leads', 'normalized_mobile')->ignore($this->route('lead')?->id);

        return $rules + ['assigned_to' => 'prohibited', 'status' => 'prohibited', 'created_by' => 'prohibited', 'source_data' => 'prohibited', 'normalized_mobile' => 'prohibited'];
    }
}
