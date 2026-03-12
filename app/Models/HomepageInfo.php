<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageInfo extends Model
{
    protected $fillable = [
        'welcome_message',
        'intro_text',
        'primary_button_text',
        'primary_button_url',
        'secondary_button_text',
        'secondary_button_url',
        'women_supported',
        'women_supported_label',
        'meals_served',
        'meals_served_label',
        'communities_reached',
        'communities_reached_label',
        'number_of_volunteers',
        'number_of_volunteers_label',
    ];
}
