<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Museum extends Model
{
    protected $fillable = [
        'name',
        'location',
        'description',
        'photo',
        'contact',
        'email',
        'website',
        'open_time',
        'close_time',
    ];

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
