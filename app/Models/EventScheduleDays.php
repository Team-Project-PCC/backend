<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class EventScheduleDays extends Model
{
    protected $table = 'event_schedule_days';

    protected $fillable = [
        'event_schedule_recurring_id',
        'start_time',
        'end_time',
    ];

    public function eventScheduleRecurring(){
        return $this->belongsTo(EventScheduleRecurring::class);
    }
}
