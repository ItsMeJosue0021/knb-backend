<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactInfo extends Model
{
    protected $fillable = [
        'telephone_number',
        'phone_number',
        'email_address',
        'physical_address'
    ];
}
