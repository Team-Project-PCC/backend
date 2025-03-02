<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'event_id',
        'category',
        'price',
        'quota',
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
}
