<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryStatement extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['salary_month' => 'date', 'snapshot' => 'array', 'other_deductions' => 'decimal:2', 'finalized_at' => 'datetime', 'payment_date' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
