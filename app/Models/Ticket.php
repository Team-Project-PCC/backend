<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'event_id',
        'ticket_category_id',
        'image_url',
        'user_id',
    ];

    public function event(){
        return $this->belongsTo(Event::class);
    }

    public function User(){
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id');
    }

    public function orders()
    {
        return $this->hasMany(TicketOrderDetails::class, 'ticket_id');
    }
}
