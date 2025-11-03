<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoFeature extends Model
{
    //Fill table with geo_features columns
    protected $fillable = ['file_upload_id', 'geometry', 'properties'];

    protected $casts = [
        'geometry' => 'array',
        'properties' => 'array',
    ];

    public function fileUpload()
    {
        return $this->belongsTo(FileUpload::class);
    }
}
