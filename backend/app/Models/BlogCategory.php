<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class BlogCategory extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'name',
        'slug',
    ];

    public function blogs()
    {
        return $this->hasMany(Blog::class, 'category_id');
    }
}
