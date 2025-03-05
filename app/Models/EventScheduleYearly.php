<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventScheduleYearly extends Model
{
    protected $table = 'event_schedule_yearlies';

    protected $fillable = [
        'event_id',
        'day',
        'month',
        'start_time',
        'end_time',
    ];

    public function event(){
        return $this->belongsTo(Event::class);
    }

    public function scheduleRecurring(){
        return $this->belongsTo(EventScheduleRecurring::class);
    }
}
