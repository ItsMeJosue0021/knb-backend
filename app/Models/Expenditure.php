<?php

namespace App\Models;

use App\Models\CashLiquidation;
use App\Models\ExpenditureItem;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

class Expenditure extends Model
{
    protected $fillable = [
        'reference_number',
        'name',
        'description',
        'amount',
        'date_incurred',
        'payment_method',
        'notes',
        'status',
        'attachment',
        'source_type',
        'source_id',
        'project_id',
    ];

    public function items() {
        return $this->hasMany(ExpenditureItem::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function cashLiquidation()
    {
        return $this->belongsTo(CashLiquidation::class, 'source_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->reference_number = 'EXP-' . strtoupper(uniqid());
        });
    }

}
