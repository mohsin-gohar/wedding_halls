<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class CheckSubscriptionExpiryCommand extends Command
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
    protected $description = 'Audit active tenant subscriptions, manage grace periods, and transition expired accounts to locked mode while preserving all historical data.';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionService $subscriptionService): int
    {
        $this->info('Starting VenueCloud SaaS Subscription Expiry Audit...');

        $results = $subscriptionService->checkAndExpireSubscriptions();

        $this->line("• Accounts moved to Grace Period: {$results['warning_count']}");
        $this->line("• Accounts moved to EXPIRED:      {$results['expired_count']}");

        if (!empty($results['details'])) {
            $this->table(
                ['Tenant ID', 'Name', 'Action', 'Timestamp'],
                array_map(function ($item) {
                    return [
                        $item['tenant_id'],
                        $item['name'],
                        $item['action'],
                        $item['expired_at'] ?? $item['grace_ends_at'] ?? 'N/A',
                    ];
                }, $results['details'])
            );
        }

        $this->info('SaaS Subscription Audit completed successfully.');
        return Command::SUCCESS;
    }
}
