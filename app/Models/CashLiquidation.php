<?php

namespace App\Models;

use App\Models\Expenditure;
use Illuminate\Database\Eloquent\Model;

class CashLiquidation extends Model
{
    protected $fillable = [
        'project_id',
        'amount',
        'date_used',
        'used_at',
        'date',
        'point_person',
        'person_responsible',
        'receipt',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date_used' => 'date',
        'used_at' => 'date',
        'date' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function expenditure()
    {
        return $this->hasOne(Expenditure::class, 'source_id')->where('source_type', 'project_liquidation');
    }
}
