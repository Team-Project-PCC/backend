<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'ticket_id',
        'method',
        'status',
        'payment_url',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
