<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class ProvisionFirstSuperAdminCommand extends Command
{
    protected $signature = 'app:provision-super-admin';

    protected $description = 'Interactively provision the first platform Super Admin on a new production installation';

    public function handle(): int
    {
        if (! app()->environment('production')) {
            $this->error('This command is reserved for an explicitly configured production installation.');

            return self::FAILURE;
        }

        $lockName = 'riwaj-grand-first-super-admin';
        $lock = DB::selectOne('SELECT GET_LOCK(?, 10) AS acquired', [$lockName]);

        if ((int) ($lock->acquired ?? 0) !== 1) {
            $this->error('Could not acquire the first-admin provisioning lock. Try again.');

            return self::FAILURE;
        }

        try {
            if ($this->hasSuperAdmin()) {
                $this->error('A Super Admin already exists. This command can provision only the first one.');

                return self::FAILURE;
            }

            $name = trim((string) $this->ask('Full name'));
            $email = strtolower(trim((string) $this->ask('Email address')));
            $phone = trim((string) $this->ask('Phone number (optional)', ''));
            $password = (string) $this->secret('New password');
            $passwordConfirmation = (string) $this->secret('Confirm new password');

            $validator = Validator::make([
                'name' => $name,
                'email' => $email,
                'phone' => $phone !== '' ? $phone : null,
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
            ], [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'phone' => ['nullable', 'string', 'max:50'],
                'password' => ['required', 'string', 'min:12', 'confirmed'],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $this->error($error);
                }

                return self::FAILURE;
            }

            if (! $this->confirm('Create the first platform Super Admin with these details?', false)) {
                $this->warn('Provisioning cancelled. No account was created.');

                return self::FAILURE;
            }

            $user = DB::transaction(function () use ($name, $email, $phone, $password): User {
                if ($this->hasSuperAdmin()) {
                    throw new \RuntimeException('A Super Admin was created by another process. No account was added.');
                }

                $role = Role::firstOrCreate([
                    'name' => 'ROLE_SUPER_ADMIN',
                    'guard_name' => 'web',
                ]);

                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'role' => 'ROLE_SUPER_ADMIN',
                    'status' => 'ACTIVE',
                    'tenant_id' => null,
                    'password' => $password,
                ]);

                $user->assignRole($role);

                AuditLog::log(
                    'SUPER_ADMIN_PROVISIONED',
                    "First platform Super Admin account [{$email}] was provisioned through the interactive production setup command.",
                    null,
                    'User',
                    (string) $user->id
                );

                return $user;
            });

            $this->newLine();
            $this->info('First Super Admin account created successfully.');
            $this->line('Account: '.$user->email);
            $this->warn('Store the credentials in your approved password manager and enable two-factor authentication after the first sign-in.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            report($exception);
            $this->error('Super Admin provisioning failed: '.$exception->getMessage());

            return self::FAILURE;
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?)', [$lockName]);
        }
    }

    private function hasSuperAdmin(): bool
    {
        return User::query()
            ->where(function ($query): void {
                $query->where('role', 'ROLE_SUPER_ADMIN')
                    ->orWhereHas('roles', function ($roles): void {
                        $roles->where('name', 'ROLE_SUPER_ADMIN')
                            ->where('guard_name', 'web');
                    });
            })
            ->exists();
    }
}
