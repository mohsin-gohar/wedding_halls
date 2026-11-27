<?php

namespace App\Livewire;

use App\Models\VendorPortfolioMedia;
use App\Support\Media;
use App\Support\PermissionMatrix;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Vendor ERP — Portfolio media manager (Phase 2).
 *
 * Vendors upload images/videos for public profile galleries. Files are
 * stored on the `public` disk under vendor-portfolio (URL derived on read via
 * Media::publicUrl). Deletes target that same disk so no orphaned files remain.
 * Storage is tenant-owned; customer-facing display only exposes the URL.
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

        if (! $user || ! PermissionMatrix::userCan($user, 'portfolio.view')) {
            abort(403, 'Unauthorized. Portfolio access is restricted to vendor staff.');
        }

        $tenantId = $user->tenant_id;

        if (! $tenantId) {
            abort(403, 'Forbidden. You do not have an active vendor organization assigned.');
        }

        $this->reloadMedia();
    }

    public function refreshMediaUrls(): void
    {
        $this->mediaUrls = [];

        foreach ($this->media as $item) {
            $this->mediaUrls[(string) $item['id']] = Media::publicUrl($item['path']);
        }
    }

    public function hasPortalPermission(string $permission): bool
    {
        return PermissionMatrix::userCan(Auth::user(), $permission);
    }

    private function assertCan(string $permission): void
    {
        abort_unless($this->hasPortalPermission($permission), 403, 'Unauthorized. Missing permission: '.$permission);
    }

    private function reloadMedia(): void
    {
        $this->media = VendorPortfolioMedia::query()
            ->where('tenant_id', Auth::user()->tenant_id)
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

    public function addMedia(): void
    {
        $this->assertCan('portfolio.manage');

        $this->resetValidation();

        $file = $this->validate([
            'mediaCaption' => ['nullable', 'string', 'max:160'],
            'mediaCategory' => ['nullable', 'string', 'max:80'],
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

        $path = $file->store('vendor-portfolio/'.$tenantId, 'public');

        if (! $path) {
            $this->addError('file', 'The portfolio file could not be stored. Please try again.');

            return;
        }

        try {
            VendorPortfolioMedia::create([
                'tenant_id' => $tenantId,
                'type' => $file->getMimeType() === 'video/mp4' || str_starts_with($file->getMimeType(), 'video/') ? 'VIDEO' : 'IMAGE',
                'path' => $path,
                'caption' => $this->mediaCaption ?: null,
                'category' => $this->mediaCategory ?: null,
            ]);
        } catch (\\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }

        $this->mediaCaption = '';
        $this->mediaCategory = '';
        $this->file = null;
        $this->reloadMedia();
        $this->feedbackMessage = 'Portfolio media uploaded successfully.';
    }

    public function removeMedia(string $mediaId): void
    {
        $this->assertCan('portfolio.manage');

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

        // Uploads use the `public` disk; deleting from any other disk would
        // leave orphaned files behind.
        Storage::disk('public')->delete($media->path);

        $media->delete();

        $this->reloadMedia();
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
