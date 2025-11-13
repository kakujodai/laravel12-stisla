<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileUpload extends Model
{
    // Allow assignment for fields
    protected $fillable = [
        'user_id',
        'filename',
        'geojson',
        'properties_metadata',
        'geometry_metadata',
        'md5',
        'title',
    ];

    // Cast JSON columns to PHP arrays
    protected $casts = [
        'properties_metadata' => 'array',
        'geometry_metadata'   => 'array',
    ];
}
