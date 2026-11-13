<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadImport extends Model
{
    protected $fillable = ['uploaded_by', 'original_filename', 'path', 'status', 'total_rows', 'imported_rows', 'duplicate_rows', 'failed_rows', 'payload', 'expires_at'];

    protected $hidden = ['path', 'payload'];

    protected function casts(): array
    {
        return ['payload' => 'encrypted:array', 'expires_at' => 'datetime'];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
