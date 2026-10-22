<?php

namespace App\Http\Requests\Leads;

use App\Models\LeadFollowup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FollowupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('followup', $this->route('lead'));
    }

    public function rules(): array
    {
        return [
            'outcome' => ['required', Rule::in(LeadFollowup::OUTCOMES)],
            'remarks' => ['nullable', 'required_if:outcome,Others', 'string', 'max:3000'],
            'followup_at' => ['required', 'date', 'before_or_equal:now', 'after_or_equal:'.$this->route('lead')->created_at->toDateTimeString()],
            'next_followup_at' => ['nullable', 'required_if:outcome,Another Call', 'date', 'after:followup_at', 'after:now'],
            'created_by' => 'prohibited', 'lead_id' => 'prohibited',
        ];
    }
}
