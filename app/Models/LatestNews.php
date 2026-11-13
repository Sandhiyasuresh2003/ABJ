<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class LatestNews extends Model
{
    protected $fillable = [
        'title',
        'description',
        'image_path',
        'published_at',
        'position',
        'project_name',
        'project_location',
        'project_about',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'date'];
    }

    public function getImageUrlAttribute(): string
    {
        return str_starts_with($this->image_path, 'templates/')
            ? asset($this->image_path)
            : asset('storage/'.$this->image_path);
    }

    public function galleryImages(): HasMany
    {
        return $this->hasMany(LatestNewsGalleryImage::class)->orderBy('position')->orderBy('id');
    }
}
