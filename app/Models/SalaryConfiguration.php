<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryConfiguration extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['effective_month' => 'date', 'basic_salary' => 'decimal:2', 'sunday_incentive' => 'decimal:2'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
