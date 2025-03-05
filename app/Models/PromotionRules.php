<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionRules extends Model
{
    protected $table = 'promotion_rules';
    protected $fillable = [
        'promotion_id',
        'rule_type',
        'rule_value',
    ];

    public function promotion(){
        return $this->belongsTo(Promotion::class);
    }
}
