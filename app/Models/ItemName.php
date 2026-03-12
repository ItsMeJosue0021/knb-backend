<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemName extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
    ];
}
