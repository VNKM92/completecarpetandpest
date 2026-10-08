<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'user_id',
        'user_name',
        'action',
        'module',
        'entity_id',
        'details',
        'ip_address',
        'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
