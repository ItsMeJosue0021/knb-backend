<?php

namespace App\Models;

use App\Models\GDSubcategory;
use Illuminate\Database\Eloquent\Model;

class GDCategory extends Model
{
    protected $fillable = ['name'];

    public function subcategories()
    {
        return $this->hasMany(GDSubcategory::class);
    }
}
