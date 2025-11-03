<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileUpload extends Model
{
    //Connect geo_features to fileUpload
    public function features()
    {
        return $this->hasMany(GeoFeature::class);
    }
}
