<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'description',
        'price',
        'capacity',
        'image',
        'status',
        'type',
    ];

    public function museum()
    {
        return $this->belongsTo(Museum::class);
    }

    public function event_schedules()
    {
        return $this->hasMany(Event_Schedules_Recurring::class);
    }
}
