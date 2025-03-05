<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventScheduleMonthly extends Model
{
    protected $table = 'event_schedule_monthlies';

    protected $fillable = [
        'event_id',
        'event_schedule_recurring_id',
        'day',
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
