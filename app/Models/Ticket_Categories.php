<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket_Categories extends Model
{
    protected $table ='ticket_categories';
    protected $fillable = [
        'event_id',
        'category',
        'price',
        'quota',
    ];

    public function event(){
        return $this->belongsTo(Event::class);
    }
}
