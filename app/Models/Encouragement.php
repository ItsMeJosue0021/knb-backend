<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Encouragement extends Model
{
    protected $fillable = [
        'title',
        'description',
        'checklist',
        'image_path'
    ];

    protected $casts = [
        'checklist' => 'array',
    ];
}



