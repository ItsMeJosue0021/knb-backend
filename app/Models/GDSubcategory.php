<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GDSubcategory extends Model
{
    protected $fillable = ['g_d_category_id', 'name'];

    public function category()
    {
        return $this->belongsTo(GDCategory::class);
    }
}
