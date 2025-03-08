<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionUsages extends Model
{
    protected $table = 'promotion_usages';
    protected $fillable = [
        'order_id',
        'promotion_id',
        'user_id',
        'used_at',
    ];

    public function promotion(){
        return $this->belongsTo(Promotion::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
