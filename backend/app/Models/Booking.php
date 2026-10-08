<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'booking_number',
        'customer_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'service_id',
        'service_name',
        'service_address',
        'suburb',
        'postcode',
        'scheduled_date',
        'time_slot',
        'square_footage',
        'rooms',
        'bathrooms',
        'addons',
        'total_price',
        'approved_price',
        'deposit_required',
        'deposit_paid',
        'balance_due',
        'quote_status',
        'status',
        'payment_status',
        'payment_gateway',
        'notes',
        'admin_notes',
        'rejection_reason',
        'reminder_sent_2days',
        'reminder_sent_date',
        'reminder_mobile_opt',
        'reminder_email_opt',
        'assigned_employee_id',
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'reminder_sent_date' => 'datetime',
        'reminder_sent_2days' => 'boolean',
        'reminder_mobile_opt' => 'boolean',
        'reminder_email_opt' => 'boolean',
        'total_price' => 'float',
        'approved_price' => 'float',
        'deposit_required' => 'float',
        'deposit_paid' => 'float',
        'balance_due' => 'float',
        'square_footage' => 'integer',
        'rooms' => 'integer',
        'bathrooms' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function assignedEmployee()
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    public function crew()
    {
        return $this->hasMany(BookingCrewMember::class, 'booking_id');
    }

    public function order()
    {
        return $this->hasOne(Order::class, 'booking_id');
    }

    public function jobReport()
    {
        return $this->hasOne(JobReport::class, 'booking_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'booking_id');
    }

    public function payments()
    {
        return $this->hasMany(PaymentTransaction::class, 'booking_id');
    }

    public function reminderLogs()
    {
        return $this->hasMany(ReminderLog::class, 'booking_id');
    }
}
