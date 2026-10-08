<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class ReminderLog extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'booking_id',
        'recipient_email',
        'recipient_phone',
        'channel',
        'scheduled_for',
        'sent_at',
        'status',
        'error',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
