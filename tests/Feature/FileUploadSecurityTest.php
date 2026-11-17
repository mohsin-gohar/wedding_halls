<?php

namespace Tests\Feature;

use App\Livewire\HallChat;
use App\Livewire\VendorPortfolioManager;
use App\Models\Hall;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VendorPortfolioMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * File-upload security regression tests (Phase 1 — security priority).
 *
 * Uploads must be validated with BOTH a strict MIME allow-list and a safe
 * extension allow-list, and must be size-limited. Client-declared extensions
 * alone are not sufficient (spoofable).
 */
class FileUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function cateringVendor(): Tenant
    {
        return Tenant::query()->where('slug', 'nawab-caterers')->firstOrFail();
    }

    private function vendorOwner(Tenant $tenant): User
    {
        return User::query()->where('tenant_id', $tenant->id)
            ->where('role', 'ROLE_TENANT_OWNER')
            ->firstOrFail();
    }

    public function test_vendor_portfolio_rejects_disallowed_mime_type(): void
    {
        Livewire::actingAs($this->vendorOwner($this->cateringVendor()))
            ->test(VendorPortfolioManager::class)
            ->set('file', UploadedFile::fake()->create('malware.php', 10, 'application/x-php'))
            ->call('addMedia')
            ->assertHasErrors('file');
    }

    public function test_vendor_portfolio_rejects_disallowed_image_mime_type(): void
    {
        Livewire::actingAs($this->vendorOwner($this->cateringVendor()))
            ->test(VendorPortfolioManager::class)
            ->set('file', UploadedFile::fake()->create('sketch.svg', 10, 'image/svg+xml'))
            ->call('addMedia')
            ->assertHasErrors('file');
    }

    public function test_vendor_portfolio_accepts_allowed_image_and_persists_it(): void
    {
        $before = VendorPortfolioMedia::query()->where('tenant_id', $this->cateringVendor()->id)->count();

        Livewire::actingAs($this->vendorOwner($this->cateringVendor()))
            ->test(VendorPortfolioManager::class)
            ->set('file', UploadedFile::fake()->image('wedding.jpg', 1200, 800))
            ->call('addMedia')
            ->assertHasNoErrors();

        $this->assertSame($before + 1, VendorPortfolioMedia::query()->where('tenant_id', $this->cateringVendor()->id)->count());
    }

    public function test_vendor_portfolio_rejects_oversized_upload(): void
    {
        Livewire::actingAs($this->vendorOwner($this->cateringVendor()))
            ->test(VendorPortfolioManager::class)
            ->set('file', UploadedFile::fake()->create('huge.jpg', 60000, 'image/jpeg')) // 60MB > 50MB cap
            ->call('addMedia')
            ->assertHasErrors('file');
    }

    public function test_hall_chat_voice_upload_rejects_disallowed_mime_type(): void
    {
        $hall = Hall::query()
            ->where('is_active', true)
            ->whereHas('tenant', fn ($q) => $q->where('verification_status', 'APPROVED'))
            ->firstOrFail();

        Livewire::test(HallChat::class, ['slug' => $hall->slug])
            ->set('voiceRecording', UploadedFile::fake()->create('note.txt', 10, 'text/plain'))
            ->call('uploadVoice')
            ->assertHasErrors('voiceRecording');
    }
}
