<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectProposedResource extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'category_id',
        'sub_category_id',
        'quantity',
        'unit',
        'notes',
        'display_order',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function categoryModel()
    {
        return $this->belongsTo(GDCategory::class, 'category_id');
    }

    public function subCategoryModel()
    {
        return $this->belongsTo(GDSubcategory::class, 'sub_category_id');
    }
}
