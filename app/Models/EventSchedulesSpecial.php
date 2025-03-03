<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSchedulesSpecial extends Model
{
    protected $table = 'event_schedules_specials';

    protected $fillable = [
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
