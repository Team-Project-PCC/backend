<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketCategory extends Model
{
    protected $table ='ticket_categories';
    protected $fillable = [
        'event_id',
        'category',
        'description',
        'price',
        'quota',
    ];

    public function event(){
        return $this->belongsTo(Event::class);
    }

    public function ticketOrderDetails()
    {
        return $this->hasMany(TicketOrderDetails::class);
    }

    public function dailyQuota()
    {
        return $this->hasMany(TicketCategoryDailyQuota::class);
    }
}
