<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketOrderDetails extends Model
{
    protected $table = 'ticket_order_details';
    protected $fillable = [
        'ticket_order_id',
        'ticket_category_id',
        'quantity',
        'price',
        'subtotal',
    ];

    public function ticketOrder()
    {
        return $this->belongsTo(TicketOrder::class);
    }

    public function ticketCategory()
    {
        return $this->belongsTo(TicketCategory::class);
    }
}
