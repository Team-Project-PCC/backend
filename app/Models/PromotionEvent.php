<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionEvent extends Model
{
    protected $table = 'promotion_events';
    protected $fillable = [
        'promotion_id',
        'event_id',
    ];

    public function promotion(){
        return $this->belongsTo(Promotion::class);
    }

    public function event(){
        return $this->belongsTo(Event::class);
    }
}
