<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class JobReport extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'booking_id',
        'employee_id',
        'visit_time',
        'completion_time',
        'status',
        'work_description',
        'fault_notes',
        'treatment_applied',
        'checklist',
        'customer_signature',
        'summary_notes',
    ];

    protected $casts = [
        'visit_time' => 'datetime',
        'completion_time' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function photos()
    {
        return $this->hasMany(JobPhoto::class, 'job_report_id');
    }
}
