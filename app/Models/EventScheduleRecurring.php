<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventScheduleRecurring extends Model
{
    protected $table = 'event_schedules_recurrings';
    protected $fillable = [
        'event_id',
        'recurring_type',
        'day',
        'start_time',
        'end_time'
    ];

    public function event(){
        return $this->belongsTo(Event::class);
    }

    public function scheduleDays(){
        return $this->hasMany(EventScheduleDays::class);
    }
}
