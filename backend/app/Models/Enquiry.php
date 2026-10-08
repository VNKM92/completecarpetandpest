<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'enquiry_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'service',
        'bedrooms',
        'bathrooms',
        'message',
        'status',
        'is_read',
        'notes',
        'ip_address',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];
}
