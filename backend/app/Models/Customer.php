<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'email',
        'phone',
        'address',
        'suburb',
        'postcode',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'customer_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id');
    }

    public function payments()
    {
        return $this->hasMany(PaymentTransaction::class, 'customer_id');
    }
}
