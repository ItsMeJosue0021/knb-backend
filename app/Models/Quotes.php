<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotes extends Model
{
    protected $fillable = [
        'title',
        'description',
        'quotes'
    ];

    protected $casts = [
        'quotes' => 'array',
    ];
}


