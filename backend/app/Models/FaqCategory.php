<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class FaqCategory extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function faqs()
    {
        return $this->hasMany(Faq::class, 'category_id');
    }
}
