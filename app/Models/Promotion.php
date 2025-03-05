<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'promotion_id',
        'event_id',
        'code',
        'status',
        'start_date',
        'end_date',
        'usage_limit',
        'usage_count',
        'type_id',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Check if the promotion is still valid.
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->is_active &&
               now()->between($this->valid_from, $this->valid_until) &&
               ($this->usage_limit === null || $this->usage_count < $this->usage_limit);
    }

    public function event()
    {
        return $this->belongsToMany(Event::class, 'event_promotions');
    }

    public function event_promotions()
    {
        return $this->hasMany(EventPromotions::class, 'promotion_id');
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_promotions', 'promotion_id', 'event_id');
    }

    public function ticket_orders()
    {
        return $this->hasMany(TicketOrder::class);
    }

    public function promotion_rules()
    {
        return $this->hasMany(PromotionRules::class);
    }

    public function promotion_type()
    {
        return $this->belongsTo(PromotionTypes::class, 'type_id');
    }

    public function promotion_usages()
    {
        return $this->hasMany(PromotionUsages::class);
    }

}
