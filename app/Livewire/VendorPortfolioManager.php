<?php

namespace App\Livewire;

use App\Models\Tenant;
use App\Models\VendorPortfolioMedia;
use App\Support\Media;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Vendor ERP — Portfolio media manager (Phase 2).
 *
 * Vendors upload images/videos for public profile galleries. Files are
 * stored under storage/app/private/vendor-portfolio (public path derived on
 * read). Storage is tenant-owned; customer-facing display only exposes the
 * derived public URL.
 */
#[Layout('components.layouts.app', ['title' => 'Portfolio — Vendor ERP'])]
class VendorPortfolioManager extends Component
{
    use WithFileUploads;

    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public array $media = [];

    public array $mediaUrls = [];

    public string $mediaCaption = '';

    public string $mediaCategory = '';

    public bool $isVideo = false;

    public string $captionPlaceholder = '';

    public string $categoryPlaceholder = '';

    public $file = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || (! $user->isTenantOwner() && ! $user->isManager() && ! $user->hasPermissionTo('portfolio.manage'))) {
            abort(403, 'Unauthorized. Portfolio management is restricted to vendor business owners.');
        }

        $tenantId = $user->tenant_id;

        if (! $tenantId) {
            abort(403, 'Forbidden. You do not have an active vendor organization assigned.');
        }

        $this->media = VendorPortfolioMedia::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('sort')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'type' => $m->type,
                'path' => $m->path,
                'caption' => $m->caption,
                'category' => $m->category,
            ])
            ->all();

        $this->refreshMediaUrls();
    }

    public function refreshMediaUrls(): void
    {
        $this->mediaUrls = [];

        foreach ($this->media as $item) {
            $this->mediaUrls[(string) $item['id']] = Media::publicUrl($item['path']);
        }
    }

    public function addMedia(): void
    {
        $this->resetValidation();

        $file = $this->validate([
            'file' => [
                'required',
                'file',
                // MIME type is authoritative; the extension must also match.
                'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/x-matroska',
                'mimes:jpeg,png,jpg,webp,mp4,mov,m4v',
                'max:51200', // 50MB
            ],
        ])['file'] ?? null;

        if (! $file) {
            return;
        }

        if (! auth()->user()) {
            abort(403);
        }

        $tenantId = auth()->user()->tenant_id;

        if (! $tenantId) {
            abort(403);
        }

        $path = $file->store('vendor-portfolio', 'public');

        VendorPortfolioMedia::create([
            'tenant_id' => $tenantId,
            'type' => $file->getMimeType() === 'video/mp4' || str_starts_with($file->getMimeType(), 'video/') ? 'VIDEO' : 'IMAGE',
            'path' => $path,
            'caption' => $this->mediaCaption ?: null,
            'category' => $this->mediaCategory ?: null,
        ]);

        $this->mediaCaption = '';
        $this->mediaCategory = '';
        $this->feedbackMessage = 'Portfolio media uploaded successfully.';
    }

    public function removeMedia(string $mediaId): void
    {
        $tenantId = Auth::user()->tenant_id;

        if (! $tenantId) {
            abort(403);
        }

        $media = VendorPortfolioMedia::query()
            ->where('tenant_id', $tenantId)
            ->find($mediaId);

        if (! $media) {
            abort(404);
        }

        File::delete($media->path);

        $media->delete();

        $this->refreshMediaUrls();
        $this->feedbackMessage = 'Portfolio media removed.';
    }

    public function render()
    {
        return view('livewire.vendor-portfolio-manager')
            ->layout('components.layouts.app', [
                'title' => 'Portfolio — Vendor ERP',
            ]);
    }
}
