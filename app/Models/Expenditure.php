<?php

namespace App\Models;

use App\Models\ExpenditureItem;
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
    ];

    public function items() {
        return $this->hasMany(ExpenditureItem::class);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->reference_number = 'EXP-' . strtoupper(uniqid());
        });
    }

}
