<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'event_id',
        'date',
        'time',
        
    ];

    public function museum(){
        return $this->belongsTo(Museum::class);
    }
}
