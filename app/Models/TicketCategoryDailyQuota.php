<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketCategoryDailyQuota extends Model
{
    protected $table = 'ticket_category_daily_quotas';
    protected $fillable = [
        'ticket_category_id',
        'date',
        'quota',
    ];

    public function ticketCategory()
    {
        return $this->belongsTo(TicketCategory::class);
    }
}
