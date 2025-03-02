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
        return $this->hasMany(Event_Schedules_Recurring::class);
    }

    public function event_schedules_special()
    {
        return $this->hasMany(Event_Schedules_Special::class);
    }

    public function ticket_categories()
    {
        return $this->hasMany(TicketCategory::class);
    }

    public function event_images()
    {
        return $this->hasMany(Event_Image::class);
    }

    public function promotions()
    {
        return $this->belongsToMany(Promotion::class, 'event_promotions', 'event_id', 'promotion_id');
    }

}
