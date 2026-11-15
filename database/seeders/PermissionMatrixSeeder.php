<?php

namespace Database\Seeders;

use App\Support\PermissionMatrix;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the granular RBAC permission matrix (see RBAC_PERMISSION_MATRIX.md).
 * Idempotent: safe to re-run; syncs without deleting user assignments.
 */
class PermissionMatrixSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allPermissions = PermissionMatrix::allPermissions();

        foreach ($allPermissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (PermissionMatrix::allRoles() as $roleName) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions(PermissionMatrix::permissionsForRole($roleName));
        }

        // Ensure every user's string role also exists as a Spatie role and is
        // attached (users may predate the matrix or be created via User::create).
        foreach (DB::table('users')->whereNull('deleted_at')->get(['id', 'role']) as $user) {
            if (! $user->role || ! in_array($user->role, PermissionMatrix::allRoles(), true)) {
                continue;
            }

            $role = Role::findByName($user->role, 'web');

            $exists = DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $user->id)
                ->where('role_id', $role->id)
                ->exists();

            if (! $exists) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $role->id,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $user->id,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('Permission matrix seeded: '.count($allPermissions).' permissions across '.count(PermissionMatrix::allRoles()).' roles.');
    }
}
