<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket_Orders extends Model
{
    protected $fillable = [
        'ticket_id',
        'quantity',
        'promotions_id',
        'total_price',
    ];
}
