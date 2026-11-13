<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['title', 'description', 'image_path', 'position'];

    public function getImageUrlAttribute(): string
    {
        return str_starts_with($this->image_path, 'templates/')
            ? asset($this->image_path)
            : asset('storage/'.$this->image_path);
    }
}
