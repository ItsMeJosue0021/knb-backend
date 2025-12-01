<?php

namespace App\Models;

use App\Models\Expenditure;
use Illuminate\Database\Eloquent\Model;

class ExpenditureItem extends Model
{
    protected $fillable = [
        'name',
        'description',
        'quantity',
        'unit_price',
        'image',
    ];

    public function expenditure() {
        return $this->belongsTo(Expenditure::class);
    }
}
