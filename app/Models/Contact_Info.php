<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact_Info extends Model
{
    protected $fillable = [
        'museum_id',
        'email',
        'phone',
        'website',
        'facebook',
        'instagram',
        'twitter',
        'youtube',
        'linkedin',
        'tiktok',
        'whatsapp',
    ];

    public function museum(){
        return $this->belongsTo(Museum::class);
    }
}
