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
    ];

    public function event(){
        return $this->belongsTo(Event::class);
    }
}
