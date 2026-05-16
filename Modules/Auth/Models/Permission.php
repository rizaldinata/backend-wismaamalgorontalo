<?php

namespace Modules\Auth\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $attributes = [
        'guard_name' => 'api',
    ];

    protected $fillable = [
        'name',
        'guard_name',
        'target',
        'description',
    ];
}
