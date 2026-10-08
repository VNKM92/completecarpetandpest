<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'status',
        'work_notes',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
