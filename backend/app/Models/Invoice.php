<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'invoice_number',
        'booking_id',
        'customer_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'subtotal',
        'gst_rate',
        'gst_amount',
        'discount',
        'total_amount',
        'deposit_paid',
        'balance_due',
        'status',
        'payment_method',
        'issued_date',
        'due_date',
        'notes',
        'terms',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'gst_rate' => 'float',
        'gst_amount' => 'float',
        'discount' => 'float',
        'total_amount' => 'float',
        'deposit_paid' => 'float',
        'balance_due' => 'float',
        'issued_date' => 'datetime',
        'due_date' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    public function payments()
    {
        return $this->hasMany(PaymentTransaction::class, 'invoice_id');
    }
}
