<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        // role + tenant_id remain assignable only via trusted server-side User::create
        // paths (register, staff manager, admin provision, seeders). Never bind
        // raw request arrays into User::create without an allow-list.
        'role',
        'status',
        'tenant_id',
        'password',
        'last_login_at',
        'base_salary',
        'email_notifications_enabled',
        'sms_notifications_enabled',
        'two_factor_enabled',
        'two_factor_secret',
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
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'base_salary' => 'decimal:2',
            'email_notifications_enabled' => 'boolean',
            'sms_notifications_enabled' => 'boolean',
            'two_factor_enabled' => 'boolean',
        ];
    }

    public function recordLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(HallWishlist::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Hall-side workforce for a tenant (excludes platform admins and customers).
     */
    public function scopeHallStaff($query, ?string $tenantId)
    {
        return $query->where('tenant_id', $tenantId)
            ->whereNotIn('role', ['ROLE_SUPER_ADMIN', 'ROLE_CUSTOMER']);
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

    /**
     * Named route this user should land on after login or a portal-role bounce.
     */
    public function portalHomeRoute(): string
    {
        if ($this->isSuperAdmin()) {
            return 'admin.dashboard';
        }

        if ($this->isCustomer()) {
            return 'customer.dashboard';
        }

        if ($this->isAccountant()) {
            return 'expenses';
        }

        if ($this->isChef()) {
            return config('platform.kitchen_module_enabled') ? 'kitchen.board' : 'inventory';
        }

        if ($this->isFrontDesk() || $this->role === 'ROLE_FLOOR_STAFF' || $this->hasRole('ROLE_FLOOR_STAFF')) {
            return 'calendar';
        }

        return 'dashboard';
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

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotifications(): int
    {
        return $this->notifications()->whereNull('read_at')->count();
    }
}
