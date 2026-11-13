<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutUsPageSetting extends Model
{
    protected $fillable = ['label', 'heading', 'description','landing_image_one', 'landing_image_two'];
}
