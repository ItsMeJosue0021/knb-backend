<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteLogo extends Model
{
    protected $fillable = [
        'image_path',
        'main_text',
        'secondary_text',
    ];
}
