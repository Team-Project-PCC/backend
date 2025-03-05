<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'valid_from',
        'valid_until',
        'max_usage',
        'current_usage',
        'is_active',
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
               ($this->max_usage === null || $this->current_usage < $this->max_usage);
    }

    public function ticket_orders()
    {
        return $this->hasMany(TicketOrder::class);
    }

    public function promotion_rules()
    {
        return $this->hasMany(PromotionRules::class);
    }

    public function promotion_usages()
    {
        return $this->hasMany(PromotionUsages::class);
    }

    public function promotion_events()
    {
        return $this->hasMany(PromotionEvent::class);
    }
}
