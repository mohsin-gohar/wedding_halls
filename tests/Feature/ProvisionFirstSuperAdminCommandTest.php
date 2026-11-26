<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProvisionFirstSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_provisions_the_first_production_super_admin_with_a_confirmed_strong_password(): void
    {
        config(['app.env' => 'production']);

        $this->artisan('app:provision-super-admin')
            ->expectsQuestion('Full name', 'Platform Owner')
            ->expectsQuestion('Email address', 'OWNER@EXAMPLE.PK')
            ->expectsQuestion('Phone number (optional)', '+92 300 1234567')
            ->expectsQuestion('New password', 'a-very-strong-password')
            ->expectsQuestion('Confirm new password', 'a-very-strong-password')
            ->expectsConfirmation('Create the first platform Super Admin with these details?', 'yes')
            ->expectsOutput('First Super Admin account created successfully.')
            ->assertExitCode(0);

        $admin = User::query()->where('email', 'owner@example.pk')->firstOrFail();

        $this->assertSame('ROLE_SUPER_ADMIN', $admin->role);
        $this->assertSame('ACTIVE', $admin->status);
        $this->assertNull($admin->tenant_id);
        $this->assertTrue($admin->isSuperAdmin());
        $this->assertTrue($admin->hasRole('ROLE_SUPER_ADMIN'));
        $this->assertTrue(password_verify('a-very-strong-password', $admin->password));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'SUPER_ADMIN_PROVISIONED',
            'entity_type' => 'User',
            'entity_id' => (string) $admin->id,
        ]);
    }

    public function test_command_is_not_available_outside_production(): void
    {
        config(['app.env' => 'testing']);

        $this->artisan('app:provision-super-admin')
            ->expectsOutput('This command is reserved for an explicitly configured production installation.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_command_refuses_to_create_a_second_super_admin(): void
    {
        config(['app.env' => 'production']);

        $existing = User::factory()->create([
            'role' => 'ROLE_SUPER_ADMIN',
            'tenant_id' => null,
            'status' => 'ACTIVE',
        ]);
        Role::firstOrCreate(['name' => 'ROLE_SUPER_ADMIN', 'guard_name' => 'web']);
        $existing->assignRole('ROLE_SUPER_ADMIN');

        $this->artisan('app:provision-super-admin')
            ->expectsOutput('A Super Admin already exists. This command can provision only the first one.')
            ->assertExitCode(1);

        $this->assertSame(1, User::query()->where('role', 'ROLE_SUPER_ADMIN')->count());
    }
}
