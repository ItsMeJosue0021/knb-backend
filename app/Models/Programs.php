<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Programs extends Model
{
    protected $fillable = [
        'title',
        'description',
        'programs'
    ];

    protected $casts = [
        'programs' => 'array',
    ];
}
