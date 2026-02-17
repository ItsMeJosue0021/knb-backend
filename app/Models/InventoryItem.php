<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = [
        'category_id',
        'sub_category_id',
        'quantity',
        'unit',
    ];

    public function categoryModel()
    {
        return $this->belongsTo(GDCategory::class, 'category_id');
    }

    public function subCategoryModel()
    {
        return $this->belongsTo(GDSubcategory::class, 'sub_category_id');
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }
}

