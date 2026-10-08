<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'order_number',
        'booking_id',
        'customer_id',
        'subtotal',
        'discount',
        'tax',
        'total_amount',
        'payment_status',
        'payment_method',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'discount' => 'float',
        'tax' => 'float',
        'total_amount' => 'float',
        'payment_date' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
