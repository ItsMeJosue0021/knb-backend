<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CashLiquidation;
use App\Models\ProjectProposedResource;
use App\Models\ProjectResource;

class Project extends Model
{
    protected $fillable = [
        'title',
        'date',
        'location',
        'description',
        'image',
        'is_event',
    ];

    public function tags()
    {
        return $this->hasMany(Tag::class);
    }

    public function resources()
    {
        return $this->hasMany(ProjectResource::class);
    }

    public function proposedResources()
    {
        return $this->hasMany(ProjectProposedResource::class)->orderBy('display_order')->orderBy('id');
    }

    public function cashLiquidations()
    {
        return $this->hasMany(CashLiquidation::class);
    }
}
