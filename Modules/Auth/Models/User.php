<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Auth\database\factories\UserFactory;
use Modules\Resident\Models\Resident;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function resident()
    {
        return $this->hasOne(Resident::class);
    }

    public function leases()
    {
        return $this->hasManyThrough(\Modules\Rental\Models\Lease::class, Resident::class);
    }

    public function hasActiveLease(): bool
    {
        return $this->leases()
            ->where('status', \Modules\Rental\Enums\LeaseStatus::ACTIVE->value)
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->startOfDay());
            })
            ->exists();
    }

    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
