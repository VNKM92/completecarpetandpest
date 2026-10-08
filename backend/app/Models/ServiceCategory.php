<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'description',
        'icon',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function services()
    {
        return $this->hasMany(Service::class, 'category_id');
    }
}
