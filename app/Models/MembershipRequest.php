<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MembershipRequest extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'proof_of_payment',
        'proof_of_identity',
        'payment_reference_number',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
