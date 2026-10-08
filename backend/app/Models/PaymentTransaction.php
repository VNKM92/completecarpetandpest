<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'transaction_number',
        'booking_id',
        'invoice_id',
        'customer_id',
        'amount',
        'currency',
        'payment_gateway',
        'payment_type',
        'status',
        'gateway_ref',
        'payer_email',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
