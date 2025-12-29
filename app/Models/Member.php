<?php

namespace App\Models;

use App\Models\User;
use App\Models\EmergencyContact;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $fillable = [
        'member_id',
        'member_number',
        'first_name',
        'middle_name',
        'last_name',
        'nick_name',
        'address',
        'dob',
        'civil_status',
        'contact_number',
        'fb_messenger_account',
        'status',
        'user_id',
    ];

    public function emergencyContact()
    {
        return $this->hasOne(EmergencyContact::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
