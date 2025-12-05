<?php

namespace App\Models;

use App\Models\Item;
use Illuminate\Database\Eloquent\Model;

class GoodsDonation extends Model
{
    protected $fillable = [
        'name',
        'email',
        'type',
        'description',
        'address',
        'quantity',
        'year',
        'month',
        'status'
    ];
    protected $casts = ['type' => 'array',];

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
