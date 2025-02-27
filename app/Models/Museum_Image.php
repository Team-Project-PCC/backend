<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Museum_Image extends Model
{
    protected $fillable = [
        'museum_id',
        'image',
        'title',
        'description',
        'is_featured',
    ];
}
