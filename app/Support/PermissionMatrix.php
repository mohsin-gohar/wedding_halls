<?php

namespace App\Support;

/**
 * Central RBAC permission matrix for the Riwaj Grand multi-vendor ecosystem.
 *
 * Permission naming: "<group>.<action>" (Spatie guard "web").
 * The legacy `users.role` string stays the primary role driver for
 * backward compatibility; Spatie adds granular, server-side enforcement.
 *
 * Design rules
 *  - Platform roles (super admin, support, finance, moderation) are separated
 *    so that no role can silently gain both financial and security authority.
 *  - Vendor roles are scoped to a single tenant; the tenant boundary is
 *    always enforced in addition to the permission check.
 *  - New role names are aliases of the legacy `ROLE_*` vocabulary where the
 *    capability is identical (see ROLE_ALIASES) so existing checks keep working.
 */
class PermissionMatrix
{
    /** All granular permissions, grouped. */
    public const GROUPS = [
        // ── Platform governance ─────────────────────────────────────
        'vendors' => ['view', 'verify', 'suspend', 'feature', 'categories.manage', 'documents.review'],
        'listings' => ['view', 'moderate', 'publish'],
        'reviews' => ['view', 'moderate', 'reply', 'manage'],
        'audit' => ['view'],
        'settings' => ['manage'],
        'roles' => ['manage'],
        'subscription' => ['manage'],
        'customers' => ['view'],

        // ── Platform operations ─────────────────────────────────────
        'support' => ['view', 'manage', 'assign'],
        'disputes' => ['view', 'create', 'manage'],
        'messages' => ['view', 'send', 'manage'],
        'leads' => ['view', 'manage'],
        'coupons' => ['view', 'manage'],
        'reports' => ['view', 'financial', 'export'],

        // ── Vendor business ─────────────────────────────────────────
        'dashboard' => ['view'],
        'business' => ['view', 'update'],
        'locations' => ['view', 'manage'],
        'services' => ['view', 'create', 'update', 'delete', 'manage'],
        'packages' => ['view', 'manage'],
        'portfolio' => ['view', 'manage'],
        'availability' => ['view', 'manage'],
        'floor-plans' => ['view', 'manage'],
        'utilities' => ['view', 'manage'],
        'rsvps' => ['view', 'manage'],
        'documents' => ['view', 'manage'],
        'staff' => ['view', 'manage', 'attendance'],
        'inventory' => ['view', 'manage'],
        'kitchen' => ['view', 'manage'],
        'events' => ['view', 'create', 'update', 'delete'],

        // ── Commercial workflow ─────────────────────────────────────
        'bookings' => ['view', 'create', 'update', 'cancel', 'manage'],
        'service-bookings' => ['view', 'create', 'update', 'cancel', 'confirm', 'complete'],
        'rfq' => ['view', 'create', 'bid', 'award', 'close'],
        'quotations' => ['view', 'create', 'approve', 'revise'],
        'contracts' => ['view', 'create', 'manage', 'approve'],
        'events-planning' => ['view', 'manage'],

        // ── Money ───────────────────────────────────────────────────
        'payments' => ['view', 'record', 'verify', 'refund', 'manage'],
        'invoices' => ['view', 'manage'],
        'payouts' => ['view', 'request', 'approve', 'manage'],
        'wallet' => ['view'],
        'commissions' => ['view', 'manage'],
        'reconciliation' => ['view', 'manage'],
        'expenses' => ['view', 'manage'],
        'payroll' => ['view', 'manage'],

        // ── Platform integration ────────────────────────────────────
        'api' => ['use'],
    ];

    /**
     * Role → permission groups mapping (roles defined via users.role strings).
     *
     * Platform staff roles are deliberately narrow: support cannot move money,
     * finance cannot manage roles, moderation cannot touch financials.
     */
    public const ROLE_GROUPS = [
        // ══ Platform ══════════════════════════════════════════════
        'ROLE_SUPER_ADMIN' => ['*'],
        'ROLE_PLATFORM_SUPPORT' => [
            'support.view', 'support.manage', 'support.assign',
            'customers.view', 'vendors.view', 'vendors.documents.review',
            'bookings.view', 'service-bookings.view', 'rfq.view',
            'disputes.view', 'disputes.create', 'disputes.manage',
            'messages.view', 'messages.send',
            'reviews.view', 'reports.view',
        ],
        'ROLE_PLATFORM_FINANCE' => [
            'payments.view', 'payments.verify', 'payments.refund', 'payments.record',
            'invoices.view', 'invoices.manage',
            'payouts.view', 'payouts.approve', 'payouts.manage',
            'wallet.view', 'commissions.view', 'commissions.manage',
            'reconciliation.view', 'reconciliation.manage',
            'reports.view', 'reports.financial', 'reports.export',
            'subscription.manage', 'vendors.view', 'audit.view',
        ],
        'ROLE_CONTENT_MODERATOR' => [
            'listings.view', 'listings.moderate', 'listings.publish',
            'reviews.view', 'reviews.moderate', 'reviews.reply',
            'portfolio.view', 'vendors.view', 'vendors.documents.review',
            'messages.view',
        ],

        // ══ Vendor / tenant staff ═════════════════════════════════
        'ROLE_TENANT_OWNER' => [
            'dashboard.view', 'business.view', 'business.update',
            'locations.view', 'locations.manage',
            'vendors.view', 'bookings.*', 'service-bookings.*',
            'rfq.view', 'rfq.bid', 'rfq.award', 'rfq.close',
            'quotations.view', 'quotations.create', 'quotations.approve', 'quotations.revise',
            'contracts.view', 'contracts.create', 'contracts.manage', 'contracts.approve',
            'payments.view', 'payments.record', 'payments.verify',
            'invoices.view', 'invoices.manage',
            'payouts.view', 'payouts.request', 'wallet.view',
            'commissions.view', 'reconciliation.view',
            'expenses.view', 'expenses.manage', 'payroll.view', 'payroll.manage',
            'staff.view', 'staff.manage', 'staff.attendance',
            'inventory.view', 'inventory.manage',
            'kitchen.view', 'kitchen.manage',
            'packages.view', 'packages.manage',
            'services.view', 'services.create', 'services.update', 'services.delete', 'services.manage',
            'portfolio.view', 'portfolio.manage',
            'availability.view', 'availability.manage',
            'floor-plans.view', 'floor-plans.manage',
            'utilities.view', 'utilities.manage',
            'rsvps.view', 'rsvps.manage',
            'documents.view', 'documents.manage',
            'events.view', 'events-planning.view', 'events-planning.manage',
            'leads.view', 'leads.manage',
            'messages.view', 'messages.send',
            'reviews.view', 'reviews.reply',
            'reports.view', 'reports.financial',
            'customers.view', 'subscription.manage', 'settings.manage',
        ],
        'ROLE_MANAGER' => [
            'dashboard.view', 'business.view',
            'bookings.view', 'bookings.update', 'bookings.cancel', 'bookings.manage',
            'service-bookings.view', 'service-bookings.update', 'service-bookings.confirm', 'service-bookings.complete', 'service-bookings.cancel',
            'rfq.view', 'rfq.bid', 'rfq.award',
            'quotations.view', 'quotations.create', 'quotations.revise',
            'staff.view', 'staff.attendance',
            'kitchen.view', 'inventory.view', 'packages.view', 'services.view',
            'availability.view', 'floor-plans.view', 'floor-plans.manage',
            'utilities.view', 'utilities.manage', 'rsvps.view', 'rsvps.manage',
            'documents.view', 'locations.view', 'locations.manage',
            'events.view', 'events-planning.view',
            'leads.view', 'messages.view', 'messages.send',
            'reviews.view', 'reports.view', 'customers.view',
        ],
        'ROLE_ACCOUNTANT' => [
            'dashboard.view', 'staff.attendance',
            'bookings.view', 'service-bookings.view',
            'payments.view', 'payments.record',
            'invoices.view', 'invoices.manage',
            'payouts.view', 'payouts.request', 'wallet.view',
            'commissions.view', 'reconciliation.view',
            'expenses.view', 'expenses.manage',
            'payroll.view', 'payroll.manage',
            'reports.view', 'reports.financial', 'reports.export',
        ],
        'ROLE_FRONT_DESK' => [
            'dashboard.view',
            'bookings.view', 'bookings.create', 'bookings.update',
            'service-bookings.view', 'service-bookings.create', 'service-bookings.update',
            'customers.view', 'rfq.view', 'rfq.create', 'rfq.bid',
            'quotations.view', 'quotations.create',
            'payments.view', 'payments.record',
            'events.view', 'messages.view', 'messages.send',
            'availability.view', 'rsvps.view', 'rsvps.manage',
        ],
        'ROLE_CHEF' => ['kitchen.view', 'kitchen.manage', 'inventory.view', 'bookings.view', 'service-bookings.view'],
        'ROLE_FLOOR_STAFF' => ['bookings.view', 'kitchen.view', 'service-bookings.view', 'floor-plans.view'],
        'ROLE_SERVICE_STAFF' => ['dashboard.view', 'service-bookings.view', 'rfq.view', 'availability.view', 'portfolio.view'],

        // ── Explicitly named vendor roles (enterprise vocabulary) ──
        'ROLE_VENDOR_OWNER' => [
            'dashboard.view', 'business.view', 'business.update',
            'locations.view', 'locations.manage',
            'vendors.view', 'bookings.*', 'service-bookings.*',
            'rfq.view', 'rfq.bid', 'rfq.award', 'rfq.close',
            'quotations.view', 'quotations.create', 'quotations.approve', 'quotations.revise',
            'contracts.view', 'contracts.create', 'contracts.manage', 'contracts.approve',
            'payments.view', 'payments.record', 'payments.verify',
            'invoices.view', 'invoices.manage',
            'payouts.view', 'payouts.request', 'wallet.view',
            'commissions.view', 'reconciliation.view',
            'expenses.view', 'expenses.manage', 'payroll.view', 'payroll.manage',
            'staff.view', 'staff.manage', 'staff.attendance',
            'inventory.view', 'inventory.manage',
            'kitchen.view', 'kitchen.manage',
            'packages.view', 'packages.manage',
            'services.view', 'services.create', 'services.update', 'services.delete', 'services.manage',
            'portfolio.view', 'portfolio.manage',
            'availability.view', 'availability.manage',
            'floor-plans.view', 'floor-plans.manage',
            'utilities.view', 'utilities.manage',
            'rsvps.view', 'rsvps.manage',
            'documents.view', 'documents.manage',
            'events.view', 'events-planning.view', 'events-planning.manage',
            'leads.view', 'leads.manage',
            'messages.view', 'messages.send',
            'reviews.view', 'reviews.reply',
            'reports.view', 'reports.financial',
            'customers.view', 'subscription.manage', 'settings.manage',
        ],
        'ROLE_VENDOR_MANAGER' => [
            'dashboard.view', 'business.view',
            'bookings.view', 'bookings.update', 'bookings.cancel', 'bookings.manage',
            'service-bookings.view', 'service-bookings.update', 'service-bookings.confirm', 'service-bookings.complete', 'service-bookings.cancel',
            'rfq.view', 'rfq.bid', 'rfq.award',
            'quotations.view', 'quotations.create', 'quotations.revise',
            'staff.view', 'staff.attendance',
            'kitchen.view', 'inventory.view', 'packages.view', 'services.view',
            'availability.view', 'floor-plans.view', 'floor-plans.manage',
            'utilities.view', 'utilities.manage', 'rsvps.view', 'rsvps.manage',
            'documents.view', 'locations.view', 'locations.manage',
            'events.view', 'events-planning.view',
            'leads.view', 'messages.view', 'messages.send',
            'reviews.view', 'reports.view', 'customers.view',
        ],
        'ROLE_VENDOR_ACCOUNTANT' => [
            'dashboard.view', 'staff.attendance',
            'bookings.view', 'service-bookings.view',
            'payments.view', 'payments.record',
            'invoices.view', 'invoices.manage',
            'payouts.view', 'payouts.request', 'wallet.view',
            'commissions.view', 'reconciliation.view',
            'expenses.view', 'expenses.manage',
            'payroll.view', 'payroll.manage',
            'reports.view', 'reports.financial', 'reports.export',
        ],
        'ROLE_BOOKING_MANAGER' => [
            'dashboard.view',
            'bookings.view', 'bookings.create', 'bookings.update', 'bookings.cancel', 'bookings.manage',
            'service-bookings.view', 'service-bookings.create', 'service-bookings.update',
            'service-bookings.confirm', 'service-bookings.complete', 'service-bookings.cancel',
            'rfq.view', 'rfq.create', 'rfq.bid',
            'rsvps.view', 'rsvps.manage',
            'quotations.view', 'quotations.create', 'quotations.revise',
            'customers.view', 'contracts.view', 'events.view',
            'payments.view', 'payments.record',
            'messages.view', 'messages.send',
            'availability.view', 'leads.view',
        ],
        'ROLE_STAFF_MEMBER' => [
            'dashboard.view',
            'service-bookings.view', 'bookings.view',
            'events.view', 'availability.view', 'portfolio.view',
        ],
        'ROLE_PHOTOGRAPHER' => [
            'dashboard.view',
            'service-bookings.view', 'bookings.view',
            'portfolio.view', 'portfolio.manage', 'availability.view',
            'events.view',
        ],
        'ROLE_CATERING_STAFF' => [
            'dashboard.view',
            'kitchen.view', 'kitchen.manage', 'inventory.view',
            'bookings.view', 'service-bookings.view',
        ],
        'ROLE_INVENTORY_MANAGER' => [
            'dashboard.view',
            'inventory.view', 'inventory.manage',
            'packages.view', 'services.view', 'reports.view',
        ],
        'ROLE_HR_MANAGER' => [
            'dashboard.view',
            'staff.view', 'staff.manage', 'staff.attendance',
            'payroll.view', 'payroll.manage', 'reports.view',
        ],

        // ══ Customer ══════════════════════════════════════════════
        'ROLE_CUSTOMER' => [],
    ];

    /**
     * New enterprise role names mapped onto the legacy vocabulary.
     *
     * The mapping keeps every existing `isXxx()` guard and middleware check
     * valid while allowing the platform to speak in the enterprise role
     * language (VENDOR_OWNER, BOOKING_MANAGER, …).
     */
    public const ROLE_ALIASES = [
        'ROLE_VENDOR_OWNER' => 'ROLE_TENANT_OWNER',
        'ROLE_VENDOR_MANAGER' => 'ROLE_MANAGER',
        'ROLE_VENDOR_ACCOUNTANT' => 'ROLE_ACCOUNTANT',
        'ROLE_CATERING_STAFF' => 'ROLE_CHEF',
    ];

    /** Flatten the matrix into permission strings. */
    public static function allPermissions(): array
    {
        $permissions = [];

        foreach (self::GROUPS as $group => $actions) {
            foreach ($actions as $action) {
                if (str_contains($action, '.')) {
                    $permissions[] = $action; // already fully-qualified
                } else {
                    $permissions[] = $group.'.'.$action;
                }
            }
        }

        return array_values(array_unique($permissions));
    }

    /** Resolve a role's permission list, expanding wildcards. */
    public static function permissionsForRole(string $role): array
    {
        $granted = self::ROLE_GROUPS[$role] ?? [];
        $all = self::allPermissions();

        if (in_array('*', $granted, true)) {
            return $all;
        }

        $resolved = [];

        foreach ($granted as $grant) {
            if (str_ends_with($grant, '.*')) {
                $prefix = substr($grant, 0, -2);

                foreach ($all as $permission) {
                    if (str_starts_with($permission, $prefix.'.')) {
                        $resolved[] = $permission;
                    }
                }
            } elseif (in_array($grant, $all, true)) {
                $resolved[] = $grant;
            }
        }

        return array_values(array_unique($resolved));
    }

    public static function allRoles(): array
    {
        return array_keys(self::ROLE_GROUPS);
    }

    /**
     * Single server-side permission gate shared by routes, navigation and
     * Livewire actions. The role matrix is checked first so legacy string-role
     * accounts stay covered even before Spatie roles/permissions are seeded.
     */
    public static function userCan(mixed $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        $role = self::canonicalRole($user->role ?? null);

        // A recognized string role is governed exclusively by this matrix.
        // Stale or manually over-granted Spatie permissions must not silently
        // expand a role beyond its declared least-privilege capabilities.
        if ($role !== null && array_key_exists($role, self::ROLE_GROUPS)) {
            return in_array($permission, self::permissionsForRole($role), true);
        }

        try {
            return (bool) $user->hasPermissionTo($permission);
        } catch (\Throwable) {
            // Permissions may not be seeded yet in a fresh environment; the
            // matrix decision above remains authoritative in that case.
            return false;
        }
    }

    /**
     * Map an alias role onto its canonical legacy equivalent.
     */
    public static function canonicalRole(?string $role): ?string
    {
        if (! $role) {
            return null;
        }

        // Prefer an explicitly declared role group. Aliases remain useful
        // for legacy route-family checks, but must not replace a distinct
        // enterprise role's own permission set.
        return array_key_exists($role, self::ROLE_GROUPS)
            ? $role
            : (self::ROLE_ALIASES[$role] ?? $role);
    }

    /**
     * Every role string that should be treated as the given canonical role.
     *
     * @return array<int, string>
     */
    public static function roleFamily(string $canonicalRole): array
    {
        $family = [$canonicalRole];

        foreach (self::ROLE_ALIASES as $alias => $target) {
            if ($target === $canonicalRole) {
                $family[] = $alias;
            }
        }

        return $family;
    }

    /**
     * Roles that may be assigned through staff management.
     * Platform staff roles are deliberately excluded.
     *
     * @return array<int, string>
     */
    public static function assignableVendorRoles(): array
    {
        $excluded = ['ROLE_SUPER_ADMIN', 'ROLE_PLATFORM_SUPPORT', 'ROLE_PLATFORM_FINANCE', 'ROLE_CONTENT_MODERATOR', 'ROLE_CUSTOMER'];

        return array_values(array_diff(self::allRoles(), $excluded));
    }

    /**
     * Roles that may never be self-assigned through public registration.
     *
     * @return array<int, string>
     */
    public static function publicRegistrationRoles(): array
    {
        return ['ROLE_TENANT_OWNER', 'ROLE_VENDOR_OWNER'];
    }
}
