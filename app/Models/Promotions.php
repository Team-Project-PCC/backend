<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotions extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'max_discount',
        'min_order',
        'valid_from',
        'valid_until',
        'usage_limit',
        'usage_count',
        'is_active'
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
        return $this->hasMany(Event_Promotions::class, 'promotion_id');
    }

    public function events()
{
    return $this->belongsToMany(Event::class, 'event_promotions', 'promotion_id', 'event_id');
}

}
