<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectResource extends Model
{
    protected $fillable = [
        'project_id',
        'item_id',
        'quantity',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
