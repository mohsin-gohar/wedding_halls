<?php

namespace App\Models;

use App\Support\PermissionMatrix;
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
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
            'two_factor_confirmed_at' => 'datetime',
            // The TOTP secret is encrypted at rest, never in plain text.
            'two_factor_secret' => 'encrypted',
            // Recovery codes are stored as SHA-256 hashes.
            'two_factor_recovery_codes' => 'array',
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

    /**
     * True when this account holds any member of a role family
     * (a canonical role plus its enterprise aliases).
     */
    public function isRoleFamily(string $canonicalRole): bool
    {
        foreach (PermissionMatrix::roleFamily($canonicalRole) as $role) {
            if ($this->role === $role || $this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function isTenantOwner(): bool
    {
        return $this->isRoleFamily('ROLE_TENANT_OWNER');
    }

    public function isFrontDesk(): bool
    {
        return $this->isRoleFamily('ROLE_FRONT_DESK');
    }

    public function isFloorStaff(): bool
    {
        return $this->role === 'ROLE_FLOOR_STAFF' || $this->hasRole('ROLE_FLOOR_STAFF');
    }

    /**
     * Users who create bookings on behalf of a venue rather than as guests.
     * Floor staff are included so /calendar bookings are not downgraded to
     * public INQUIRY records.
     */
    public function isVenueOperationsStaff(): bool
    {
        return $this->isSuperAdmin()
            || $this->isTenantOwner()
            || $this->isManager()
            || $this->isFrontDesk()
            || $this->isFloorStaff();
    }

    public function isChef(): bool
    {
        return $this->isRoleFamily('ROLE_CHEF');
    }

    public function isManager(): bool
    {
        return $this->isRoleFamily('ROLE_MANAGER');
    }

    public function isAccountant(): bool
    {
        return $this->isRoleFamily('ROLE_ACCOUNTANT');
    }

    public function isCustomer(): bool
    {
        return $this->role === 'ROLE_CUSTOMER' || $this->hasRole('ROLE_CUSTOMER');
    }

    // ── Enterprise platform roles ──────────────────────────────────

    public function isPlatformSupport(): bool
    {
        return $this->role === 'ROLE_PLATFORM_SUPPORT' || $this->hasRole('ROLE_PLATFORM_SUPPORT');
    }

    public function isPlatformFinance(): bool
    {
        return $this->role === 'ROLE_PLATFORM_FINANCE' || $this->hasRole('ROLE_PLATFORM_FINANCE');
    }

    public function isContentModerator(): bool
    {
        return $this->role === 'ROLE_CONTENT_MODERATOR' || $this->hasRole('ROLE_CONTENT_MODERATOR');
    }

    /** Platform operations staff (support / finance / moderation). */
    public function isPlatformStaff(): bool
    {
        return $this->isPlatformSupport() || $this->isPlatformFinance() || $this->isContentModerator();
    }

    // ── Category-specific vendor roles ─────────────────────────────

    public function isPhotographer(): bool
    {
        return $this->role === 'ROLE_PHOTOGRAPHER' || $this->hasRole('ROLE_PHOTOGRAPHER');
    }

    public function isBookingManager(): bool
    {
        return $this->isRoleFamily('ROLE_FRONT_DESK');
    }

    public function isStaffMember(): bool
    {
        return $this->role === 'ROLE_STAFF_MEMBER' || $this->hasRole('ROLE_STAFF_MEMBER');
    }

    public function isInventoryManager(): bool
    {
        return $this->role === 'ROLE_INVENTORY_MANAGER' || $this->hasRole('ROLE_INVENTORY_MANAGER');
    }

    public function isHrManager(): bool
    {
        return $this->role === 'ROLE_HR_MANAGER' || $this->hasRole('ROLE_HR_MANAGER');
    }

    /**
     * Vendor-side staff member of a tenant business (never a customer).
     */
    public function isVendorStaff(): bool
    {
        return ! $this->isCustomer() && ! $this->isSuperAdmin() && $this->tenant_id !== null;
    }

    /**
     * Route name of the dashboard that fits this user's business category.
     *
     * Photography, catering, decoration, beauty and planning businesses each
     * have a dedicated dashboard; every other business uses the generic ERP
     * dashboard.
     */
    public function dashboardRoute(): string
    {
        $category = $this->tenant?->vendorCategory?->slug;

        return match ($category) {
            'photography' => 'vendor.dashboard.photography',
            'catering' => 'vendor.dashboard.catering',
            'decoration' => 'vendor.dashboard.decoration',
            'beauty-bridal' => 'vendor.dashboard.beauty-bridal',
            'wedding-planners' => 'vendor.dashboard.wedding-planners',
            default => 'dashboard',
        };
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

        // Platform support/finance/moderator accounts do not pass the
        // super-admin gate on /admin. Route them to the shared account surface
        // they can actually open instead of bouncing to a guaranteed 403.
        if ($this->isPlatformStaff() && ! $this->tenant_id) {
            return 'support.center';
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

        // Tenant owners → route to category-specific dashboard if available
        if ($this->isTenantOwner() && $this->tenant && $this->tenant->vendorCategory) {
            return $this->dashboardRoute();
        }

        if ($this->isTenantOwner() || $this->isManager() || $this->isAccountant()) {
            return 'dashboard';
        }

        // Remaining vendor roles (staff member, photographer, inventory, …)
        // cannot open the role-guarded ERP dashboard. Land them on the shared
        // notification surface, which every active tenant staff account may
        // open, instead of redirecting to a route that bounces them back.
        return 'notifications';
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
