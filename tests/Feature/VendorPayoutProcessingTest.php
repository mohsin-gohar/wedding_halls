<?php

namespace Tests\Feature;

use App\Exceptions\ServiceBookingException;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VendorPayout;
use App\Models\VendorWallet;
use App\Services\ServiceBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorPayoutProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_processing_a_payout_records_processed_at_and_debits_the_wallet(): void
    {
        [$tenant, $processor] = $this->makeFixtures();

        $wallet = VendorWallet::create([
            'tenant_id' => $tenant->id,
            'user_id' => $processor->id,
            'wallet_provider' => 'BANK_TRANSFER',
            'account_id' => 'PB-001',
            'account_name' => 'Riwaj Vendor Account',
            'balance' => 1000,
            'is_primary' => true,
        ]);

        $payout = VendorPayout::create([
            'tenant_id' => $tenant->id,
            'amount' => 250,
            'method' => 'CASH',
            'status' => 'REQUESTED',
            'requested_at' => now(),
        ]);

        $result = app(ServiceBookingService::class)->processVendorPayout($payout, (int) $processor->id);

        $this->assertSame('PAID', $result->status);
        $this->assertNotNull($result->processed_at, 'processed_at must be persisted when a payout is paid.');
        $this->assertSame((int) $processor->id, (int) $result->processed_by);
        $this->assertSame('750.00', (string) $wallet->fresh()->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'vendor_wallet_id' => $wallet->id,
            'vendor_payout_id' => $payout->id,
            'type' => 'DEBIT',
            'source' => 'PAYOUT',
            'amount' => 250,
            'balance_after' => 750,
        ]);
    }

    public function test_a_paid_payout_cannot_be_processed_twice(): void
    {
        [$tenant, $processor] = $this->makeFixtures();

        $payout = VendorPayout::create([
            'tenant_id' => $tenant->id,
            'amount' => 100,
            'method' => 'CASH',
            'status' => 'PAID',
            'requested_at' => now(),
            'processed_at' => now(),
        ]);

        $this->expectException(ServiceBookingException::class);

        app(ServiceBookingService::class)->processVendorPayout($payout, (int) $processor->id);
    }

    /**
     * @return array{0: Tenant, 1: User}
     */
    private function makeFixtures(): array
    {
        $tenant = Tenant::create([
            'name' => 'Payout Processing Banquets',
            'slug' => 'payout-processing',
            'email' => 'payout@riwaj.pk',
            'phone' => '+92 300 4445577',
            'city' => 'Lahore',
            'subscription_plan' => 'STARTER',
            'currency' => 'PKR',
            'verification_status' => 'APPROVED',
            'is_active' => true,
        ]);

        $processor = User::factory()->create([
            'role' => 'ROLE_SUPER_ADMIN',
            'status' => 'ACTIVE',
        ]);

        return [$tenant, $processor];
    }
}
