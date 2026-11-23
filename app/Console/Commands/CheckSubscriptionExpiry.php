<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\SubscriptionPayment;
use Carbon\Carbon;

class CheckSubscriptionExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:check-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and process subscription expirations';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Checking subscription expirations...');

        $now = Carbon::now();

        // Find tenants with trial subscriptions that have expired
        $tenants = Tenant::where('subscription_status', 'ACTIVE')
            ->whereNotNull('subscription_expires_at')
            ->where('subscription_expires_at', '<', $now)
            ->get();

        $processed = 0;

        foreach ($tenants as $tenant) {
            $tenant->update(['subscription_status': 'EXPIRED']);

            // Cancel any active bookings for this tenant
            // $tenant->bookings()->where('status', 'CONFIRMED')->each->update(['status': 'EXPIRED']);

            $processed++;
        }

        if ($processed > 0) {
            $this->info("Processed {$processed} expired subscriptions.");
        } else {
            $this->info('No subscriptions expired.');
        }

        return Command::SUCCESS;
    }
}