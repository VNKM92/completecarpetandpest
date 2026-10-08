<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasCustomId;

    protected $table = 'media';

    protected $fillable = [
        'id',
        'filename',
        'url',
        'mime_type',
        'size',
        'alt_text',
        'folder',
    ];

    protected $casts = [
        'size' => 'integer',
    ];
}
