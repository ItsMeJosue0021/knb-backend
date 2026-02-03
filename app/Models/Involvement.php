<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Involvement extends Model
{
    protected $fillable = [
        'title',
        'description',
        'involvements'
    ];

    protected $casts = [
        'involvements' => 'array',
    ];
}





