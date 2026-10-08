<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'category_id',
        'short_desc',
        'description',
        'price_starting',
        'price_unit',
        'duration',
        'icon',
        'hero_image',
        'gallery',
        'features',
        'tab_data',
        'meta_title',
        'meta_desc',
        'meta_keywords',
        'canonical_url',
        'og_image',
        'schema_type',
        'is_featured',
        'is_active',
        'order',
    ];

    protected $casts = [
        'price_starting' => 'float',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'service_id');
    }
}
