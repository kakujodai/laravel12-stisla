<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardWidget extends Model
{
    protected $casts = [
        'metadata' => 'array',
        'is_locked' => 'boolean',
    ];

    protected $fillable = [
        'user_id', 'dashboard_id', 'widget_type_id', 'name', 'order', 'metadata', 'layout_column', 'is_locked'
    ];
}
