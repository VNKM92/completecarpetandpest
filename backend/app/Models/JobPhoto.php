<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class JobPhoto extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'job_report_id',
        'photo_url',
        'type',
        'caption',
        'uploaded_by',
    ];

    public function jobReport()
    {
        return $this->belongsTo(JobReport::class, 'job_report_id');
    }
}
