<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'museum_id',
        'name',
        'description',
        'date',
        'price',
        'image',
    ];

    public function museum(){
        return $this->belongsTo(Museum::class);
    }

    public function images(){
        return $this->hasMany(Event_Image::class);
    }

    public function schedules(){
        return $this->hasMany(Schedule::class);
    }
}
