<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'recipient',
        'subject',
        'body',
        'status',
        'error',
    ];
}
