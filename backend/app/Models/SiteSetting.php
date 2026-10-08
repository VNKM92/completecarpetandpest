<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'key',
        'value',
        'group',
        'label',
    ];
}
