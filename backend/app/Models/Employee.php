<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'employee_code',
        'user_id',
        'name',
        'email',
        'phone',
        'designation',
        'department',
        'status',
        'hourly_rate',
        'skills',
        'emergency_contact',
        'address',
        'license_number',
        'join_date',
    ];

    protected $casts = [
        'hourly_rate' => 'float',
        'join_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBookings()
    {
        return $this->hasMany(Booking::class, 'assigned_employee_id');
    }

    public function crewAssignments()
    {
        return $this->hasMany(BookingCrewMember::class, 'employee_id');
    }

    public function jobReports()
    {
        return $this->hasMany(JobReport::class, 'employee_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'employee_id');
    }
}
