<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventScheduleSpecial extends Model
{
    protected $table = 'event_schedules_specials';

    protected $fillable = [
        'event_id',
        'start_datetime',
        'end_datetime',
    ];

    public function event(){
        return $this->belongsTo(Event::class);
    }
}
