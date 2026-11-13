<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PettyCashTransaction extends Model
{
    protected $fillable = ['transaction_date', 'type', 'category', 'product_name', 'quantity', 'rate', 'description', 'amount', 'receipt_reference', 'created_by'];
    protected function casts(): array { return ['transaction_date' => 'date', 'quantity' => 'decimal:3', 'rate' => 'decimal:2', 'amount' => 'decimal:2']; }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
