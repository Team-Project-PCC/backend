<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event_Image extends Model
{
    protected $table = 'event_images';
    protected $fillable = [
        'event_id',
        'name',
        'url',
    ];

    public function event(){
        return $this->belongsTo(Event::class);
    }
}
