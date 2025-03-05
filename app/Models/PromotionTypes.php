<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionTypes extends Model
{
    protected $table = 'promotion_types';
    protected $fillable = [
        'name',
    ];

    public function promotions(){
        return $this->hasMany(Promotion::class);
    }
}
