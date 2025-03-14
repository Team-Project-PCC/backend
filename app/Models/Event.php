<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'description',
        'image',
        'status',
        'type',
    ];

    public function event_schedules_recurring()
    {
        return $this->hasMany(EventScheduleRecurring::class);
    }

    public function event_schedules_special()
    {
        return $this->hasMany(EventScheduleSpecial::class);
    }

    public function ticket_categories()
    {
        return $this->hasMany(TicketCategory::class);
    }

    public function event_images()
    {
        return $this->hasMany(EventImage::class);
    }

    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'event_promotions', 'event_id', 'promotion_id');
    }

    public function promotion_event()
    {
        return $this->hasMany(PromotionEvent::class, 'event_id');
    }

    public function recurringSchedules()
    {
        return $this->hasMany(EventScheduleRecurring::class);
    }

    public function specialSchedules()
    {
        return $this->hasMany(EventScheduleSpecial::class);
    }
}
