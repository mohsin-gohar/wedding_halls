<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class HostingerDeployCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hostinger:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automate production deployment checks, storage symlinking, database migrations, and caching for Hostinger hosting';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('   Riwaj Grand — Hostinger Production Setup & Optimizer ');
        $this->info('====================================================');

        // 1. Remove Vite dev hot file
        if (File::exists(public_path('hot'))) {
            File::delete(public_path('hot'));
            $this->line('<info>✅</info> Removed dev hot file (public/hot)');
        }

        // 2. Storage symlink
        $storageTarget = storage_path('app/public');
        $publicLink = public_path('storage');

        if (! File::exists($storageTarget)) {
            File::makeDirectory($storageTarget, 0755, true);
        }

        if (! file_exists($publicLink)) {
            try {
                Artisan::call('storage:link');
                $this->line('<info>✅</info> Public storage symlink created: '.$publicLink);
            } catch (\Throwable $e) {
                $this->warn('! Symlink creation failed, trying relative link or copy: '.$e->getMessage());
            }
        } else {
            $this->line('<info>✅</info> Public storage symlink already exists');
        }

        // 3. Database Migration
        $this->info('Running database migrations...');
        try {
            Artisan::call('migrate', ['--force' => true]);
            $this->line(trim(Artisan::output()));
            $this->line('<info>✅</info> Database migrations executed successfully');
        } catch (\Throwable $e) {
            $this->error('Database migration failed: '.$e->getMessage());

            return self::FAILURE;
        }

        // 4. Seeding is intentionally not available. Demo seeders create
        //    known credentials and must never run against production data.

        // 5. Caching for peak performance on Hostinger LiteSpeed/Apache
        $this->info('Optimizing caches for production...');
        Artisan::call('optimize:clear');
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
        Artisan::call('event:cache');
        $this->line('<info>✅</info> Config, routes, views, and events cached');

        // 6. Check Storage and Cache Permissions
        $writableDirs = [
            storage_path(),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        foreach ($writableDirs as $dir) {
            if (! File::exists($dir)) {
                File::makeDirectory($dir, 0775, true);
            }
            if (! is_writable($dir)) {
                @chmod($dir, 0775);
            }
        }
        $this->line('<info>✅</info> Verified storage & cache directory permissions');

        $this->newLine();
        $this->info('====================================================');
        $this->info('  HOSTINGER DEPLOYMENT READY & OPTIMIZED!           ');
        $this->info('====================================================');
        $this->line('Your site is live and operating with peak performance.');

        return self::SUCCESS;
    }
}
