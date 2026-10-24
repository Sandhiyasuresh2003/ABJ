<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyDocument extends Model
{
    public const TYPES = ['ec' => 'EC', 'patta_chitta' => 'Patta Chitta'];

    protected $fillable = ['title', 'type', 'original_name', 'path', 'size', 'uploaded_by'];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
