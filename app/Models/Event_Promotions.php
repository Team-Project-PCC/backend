<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event_Promotions extends Model
{
    protected $table = 'event_promotions';

    protected $fillable = [
        'event_id',
        'promotion_id'
    ];

    public function event(){
        return $this->belongsTo(Event::class);
    }

    public function promotion(){
        return $this->belongsTo(Promotion::class);
    }
}
