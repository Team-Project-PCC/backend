<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    protected $fillable = [
        'museum_id',
        'category',
        'price',
    ];

    public function event(){
        return $this->belongsTo(Event::class);
    }
}
