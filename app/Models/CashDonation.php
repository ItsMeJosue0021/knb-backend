<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class CashDonation extends Model
{
    protected $fillable = [
        'name',
        'email',
        'amount',
        'drop_off_date',
        'drop_off_time',
        'drop_off_address',
        'month',
        'year',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($donation) {
            if (empty($donation->donation_tracking_number)) {
                $donation->donation_tracking_number = 'CDN-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
            }
        });
    }
}
