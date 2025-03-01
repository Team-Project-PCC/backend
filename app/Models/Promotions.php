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
}
