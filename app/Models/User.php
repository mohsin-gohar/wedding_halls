<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'tenant_id',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'ROLE_SUPER_ADMIN' || $this->hasRole('ROLE_SUPER_ADMIN');
    }

    public function isTenantOwner(): bool
    {
        return $this->role === 'ROLE_TENANT_OWNER' || $this->hasRole('ROLE_TENANT_OWNER');
    }

    public function isFrontDesk(): bool
    {
        return $this->role === 'ROLE_FRONT_DESK' || $this->hasRole('ROLE_FRONT_DESK');
    }

    public function isChef(): bool
    {
        return $this->role === 'ROLE_CHEF' || $this->hasRole('ROLE_CHEF');
    }

    public function isManager(): bool
    {
        return $this->role === 'ROLE_MANAGER' || $this->hasRole('ROLE_MANAGER');
    }

    public function isAccountant(): bool
    {
        return $this->role === 'ROLE_ACCOUNTANT' || $this->hasRole('ROLE_ACCOUNTANT');
    }

    public function isCustomer(): bool
    {
        return $this->role === 'ROLE_CUSTOMER' || $this->hasRole('ROLE_CUSTOMER');
    }

    public function customerBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'customer_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StaffAttendance::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(StaffLeave::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(StaffPayroll::class);
    }
}
