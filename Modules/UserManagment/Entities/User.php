<?php

namespace Modules\UserManagment\Entities;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable, HasRoles;

    protected $table = 'users'; // points to your migration table

    protected $fillable = [
        'user_id',
        'phone',
        'address',
        'address_latitude',
        'address_longitude',
        'city',
        'postal_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'address_latitude' => 'float',
        'address_longitude' => 'float',
    ];

    /**
     * @method bool hasRole(string|array $roles)
     * @method bool hasAnyRole(array|string $roles)
     */
}
