<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'title',
        'slug',
        'heading',
        'subheading',
        'content',
        'banner_image',
        'meta_title',
        'meta_desc',
        'meta_keywords',
        'canonical_url',
        'og_image',
        'robots',
        'custom_schema',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];
}
