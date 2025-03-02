<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketOrder extends Model
{
    protected $table = 'ticket_orders';
    protected $fillable = [
        'ticket_id',
        'quantity',
        'promotions_id',
        'total_price',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }
}
