<?php

namespace App\Models;

use App\Models\Traits\HasCustomId;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasCustomId;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'category',
        'description',
    ];

    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class, 'permission_id');
    }
}
