<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class BookingCrewMember extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'booking_id',
        'employee_id',
        'role',
        'is_lead',
        'assigned_at',
        'notes',
    ];

    protected $casts = [
        'is_lead' => 'boolean',
        'assigned_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
