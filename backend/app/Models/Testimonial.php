<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'client_name',
        'role',
        'location',
        'avatar',
        'rating',
        'review',
        'source',
        'is_approved',
        'order',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
        'order' => 'integer',
    ];
}
