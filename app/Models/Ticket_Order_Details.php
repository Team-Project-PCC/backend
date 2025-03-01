<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket_Order_Details extends Model
{
    protected $fillable = [
        'ticket_order_id',
        'ticket_id',
        'quantity',
        'price',
        'subtotal',
    ];
}
