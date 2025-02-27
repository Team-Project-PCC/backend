<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event_Image extends Model
{
    protected $fillable = [
        'event_id',
        'image',
        'title',
        'description',
        'is_featured',
    ];

    public function event(){
        return $this->belongsTo(Event::class);
    }
}
