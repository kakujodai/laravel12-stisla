<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dashboard extends Model
{
    protected $casts = [
        'is_locked' => 'boolean',
    ];

    protected $fillable = [
        'user_id', 'name', 'is_locked'
    ];
}
