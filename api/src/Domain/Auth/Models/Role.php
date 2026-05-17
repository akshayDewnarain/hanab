<?php

namespace Domain\Auth\Models;

use Domain\Auth\Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    /** {@inheritdoc} */
    protected $fillable = [
        'name',
        'guard_name',
    ];

    protected static function newFactory(): RoleFactory
    {
        return RoleFactory::new();
    }
}
