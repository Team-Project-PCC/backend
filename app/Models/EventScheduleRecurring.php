<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventScheduleRecurring extends Model
{
    protected $table = 'event_schedules_recurrings';
    protected $fillable = [
        'event_id',
        'date',
        'recurring_type',
    ];

    public function event(){
        return $this->belongsTo(Event::class);
    }

    public function scheduleDays(){
        return $this->hasMany(EventScheduleDays::class);
    }

    public function scheduleMonthly(){
        return $this->hasMany(EventScheduleMonthly::class);
    }

    public function scheduleWeekly(){
        return $this->hasMany(EventScheduleWeekly::class);
    }

    public function scheduleYearly(){
        return $this->hasMany(EventScheduleYearly::class);
    }
}
