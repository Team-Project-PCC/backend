<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketOrder extends Model
{
    protected $table = 'ticket_orders';
    protected $fillable = [
        'event_id',
        'user_id',
        'ticket_id',
        'total_quantity',
        'promotions_id',
        'total_price',
        'date',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function ticketOrderDetails()
    {
        return $this->hasMany(TicketOrderDetails::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
