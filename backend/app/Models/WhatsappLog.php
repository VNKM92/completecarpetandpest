<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'phone',
        'message',
        'direction',
        'status',
    ];
}
