<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'museum_id',
        'day',
        'open',
        'close',
    ];

    public function museum(){
        return $this->belongsTo(Museum::class);
    }
}
