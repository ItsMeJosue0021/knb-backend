<?php

namespace App\Models;

use App\Models\GoodsDonation;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        "name",
        "image",
        "category",
        "sub_category",
        "quantity",
        "unit",
        "notes"
    ];

    public function goodsDonation() {
        return $this->belongsTo(GoodsDonation::class);
    }
}
