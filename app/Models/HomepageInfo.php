<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageInfo extends Model
{
    protected $fillable = [
        'welcome_message',
        'intro_text',
        'women_supported',
        'meals_served',
        'communities_reached',
        'number_of_volunteers'
    ];
}
