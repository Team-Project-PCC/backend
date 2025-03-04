<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventScheduleSpecial extends Model
{
    protected $table = 'event_schedules_specials';

    protected $fillable = [
        'event_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time'
    ];

    public function event(){
        return $this->belongsTo(Event::class);
    }
}
