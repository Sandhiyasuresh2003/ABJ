<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LatestNewsGalleryImage extends Model
{
    protected $fillable = ['image_path', 'position'];

    public function latestNews(): BelongsTo
    {
        return $this->belongsTo(LatestNews::class);
    }

    public function getImageUrlAttribute(): string
    {
        return asset('storage/'.$this->image_path);
    }
}
