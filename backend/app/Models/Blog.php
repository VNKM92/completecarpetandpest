<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_img',
        'author',
        'read_time',
        'category_id',
        'tags',
        'meta_title',
        'meta_desc',
        'meta_keywords',
        'canonical_url',
        'og_image',
        'status',
        'published_at',
    ];

    protected $casts = [
        'read_time' => 'integer',
        'published_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }
}
