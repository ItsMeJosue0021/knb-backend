<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminActivityLog extends Model
{
    /** @use HasFactory<\Database\Factories\AdminActivityLogFactory> */
    use HasFactory;

    protected $table = 'admin_activity_logs';

    protected $fillable = [
        'actor_id',
        'action',
        'method',
        'path',
        'severity',
        'status_code',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
