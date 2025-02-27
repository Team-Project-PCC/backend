<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Museum extends Model
{
    protected $fillable = [
        'name',
        'location',
        'description',
        'email',
        'phone',
        'website',
    ];
}
