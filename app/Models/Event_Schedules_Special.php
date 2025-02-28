<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event_Schedules_Special extends Model
{
    protected $table = [
        'event_id',
        'start_date',
        'end_date',
        'start_ti,e',
        'end_time'
    ];

    public function event(){
        return $this->belongsTo(Event::class);
    }
}
