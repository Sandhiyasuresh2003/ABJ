<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientTestimonial extends Model
{
    protected $fillable = ['name', 'role', 'feedback', 'image_path', 'position'];

    public function getImageUrlAttribute(): string
    {
        return str_starts_with($this->image_path, 'templates/')
            ? asset($this->image_path)
            : asset('storage/'.$this->image_path);
    }
}
