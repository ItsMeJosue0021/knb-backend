<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
}
