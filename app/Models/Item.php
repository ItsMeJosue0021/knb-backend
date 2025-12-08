<?php

namespace App\Models;

use App\Models\GoodsDonation;
use App\Models\GDCategory;
use App\Models\GDSubcategory;
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

    public function categoryModel()
    {
        return $this->belongsTo(GDCategory::class, 'category');
    }

    public function subCategoryModel()
    {
        return $this->belongsTo(GDSubcategory::class, 'sub_category');
    }
}
