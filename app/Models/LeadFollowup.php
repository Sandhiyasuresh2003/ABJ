<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFollowup extends Model
{
    public const OUTCOMES = ['RNR', 'WhatsApp Details Shared', 'Another Call', 'Not Interested', 'Wrong Enquiry', 'Others'];

    protected $fillable = ['lead_id', 'outcome', 'remarks', 'followup_at', 'next_followup_at', 'created_by'];

    protected function casts(): array
    {
        return ['followup_at' => 'datetime', 'next_followup_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
