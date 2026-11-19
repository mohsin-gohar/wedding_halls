<?php

namespace App\Livewire;

use App\Models\VendorCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Super Admin — hierarchical vendor category & subcategory manager.
 *
 * Categories are the marketplace taxonomy: every vendor business, service
 * card, RFQ and booking mode resolves through them. This screen is the
 * no-developer-required control surface (create / edit / nest / reorder /
 * enable / SEO / image) and it is platform-admin only — enforced both by
 * the `super.admin` route middleware and again here (defence in depth).
 */
#[Layout('components.layouts.app', ['title' => 'Categories — Super Admin'])]
class AdminCategoryManager extends Component
{
    public ?string $feedbackMessage = null;

    public ?string $errorMessage = null;

    public bool $showCategoryModal = false;

    public bool $isEditing = false;

    public ?int $editingCategoryId = null;

    // Form fields
    public string $name = '';

    public string $slug = '';

    public string $tagline = '';

    public string $icon = '';

    public string $image = '';

    public string $heroColor = '#C6A15B';

    public string $bookingMode = 'DATE_EVENT';

    public string $description = '';

    public string $seoTitle = '';

    public string $seoDescription = '';

    public ?int $parentId = null;

    /** Candidate parents for the subcategory selector (cycle-safe). */
    public array $parentOptions = [];

    public function mount(): void
    {
        $this->assertPlatformAdmin();
        $this->reloadParentOptions();
    }

    protected function assertPlatformAdmin(): void
    {
        $user = Auth::user();

        abort_unless(
            $user && ($user->role === 'ROLE_SUPER_ADMIN' || $user->isSuperAdmin()),
            403,
            'Access denied. Super Admin privileges are required.'
        );
    }

    private function reloadParentOptions(?int $excludeId = null): void
    {
        $excludeIds = [];

        if ($excludeId !== null) {
            $editing = VendorCategory::query()->find($excludeId);

            if ($editing) {
                // Never offer the category itself or its descendants as a
                // parent — that would create a hierarchy cycle.
                $excludeIds = array_merge(
                    [$editing->id],
                    $this->descendantIds($editing)
                );
            }
        }

        $this->parentOptions = VendorCategory::query()
            ->whereNull('parent_id')
            ->whereNotIn('id', $excludeIds)
            ->orderBy('sort')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($c) => ['id' => (int) $c->id, 'name' => $c->name])
            ->values()
            ->toArray();
    }

    private function descendantIds(VendorCategory $category): array
    {
        $ids = [];
        $queue = [$category->id];

        while ($queue !== []) {
            $current = array_pop($queue);
            $children = VendorCategory::query()
                ->where('parent_id', $current)
                ->get(['id']);

            foreach ($children as $child) {
                $ids[] = $child->id;
                $queue[] = $child->id;
            }
        }

        return $ids;
    }

    /**
     * Full taxonomy tree for the screen (top-level rows + children).
     */
    public function getTreeProperty()
    {
        return VendorCategory::query()
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('sort')->orderBy('name')])
            ->orderBy('sort')
            ->orderBy('name')
            ->get();
    }

    public function openCreateModal(?int $parentId = null): void
    {
        $this->assertPlatformAdmin();

        $this->isEditing = false;
        $this->editingCategoryId = null;
        $this->name = '';
        $this->slug = '';
        $this->tagline = '';
        $this->icon = '';
        $this->image = '';
        $this->heroColor = '#C6A15B';
        $this->bookingMode = 'DATE_EVENT';
        $this->description = '';
        $this->seoTitle = '';
        $this->seoDescription = '';
        $this->parentId = $parentId;
        $this->reloadParentOptions();
        $this->errorMessage = null;
        $this->showCategoryModal = true;
    }

    public function openEditModal(int $categoryId): void
    {
        $this->assertPlatformAdmin();

        $category = VendorCategory::query()->find($categoryId);

        if (! $category) {
            abort(404);
        }

        $this->isEditing = true;
        $this->editingCategoryId = (int) $category->id;
        $this->name = (string) $category->name;
        $this->slug = (string) $category->slug;
        $this->tagline = (string) ($category->tagline ?? '');
        $this->icon = (string) ($category->icon ?? '');
        $this->image = (string) ($category->image ?? '');
        $this->heroColor = (string) ($category->hero_color ?? '#C6A15B');
        $this->bookingMode = (string) $category->booking_mode;
        $this->description = (string) ($category->description ?? '');
        $this->seoTitle = (string) ($category->seo_title ?? '');
        $this->seoDescription = (string) ($category->seo_description ?? '');
        $this->parentId = $category->parent_id !== null ? (int) $category->parent_id : null;
        $this->reloadParentOptions($this->editingCategoryId);
        $this->errorMessage = null;
        $this->showCategoryModal = true;
    }

    public function closeModal(): void
    {
        $this->showCategoryModal = false;
        $this->editingCategoryId = null;
        $this->errorMessage = null;
    }

    public function saveCategory(): void
    {
        $this->assertPlatformAdmin();
        $this->errorMessage = null;

        $this->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'tagline' => ['nullable', 'string', 'max:180'],
            'icon' => ['nullable', 'string', 'max:60'],
            'image' => ['nullable', 'string', 'max:255'],
            'heroColor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'bookingMode' => ['required', 'string', 'in:'.implode(',', VendorCategory::BOOKING_MODES)],
            'description' => ['nullable', 'string', 'max:1000'],
            'seoTitle' => ['nullable', 'string', 'max:160'],
            'seoDescription' => ['nullable', 'string', 'max:255'],
            'parentId' => ['nullable', 'integer', 'exists:vendor_categories,id'],
        ]);

        $slug = $this->slug !== '' ? $this->slug : Str::slug($this->name);

        $duplicate = VendorCategory::query()
            ->where('slug', $slug)
            ->when($this->editingCategoryId, fn ($q) => $q->where('id', '!=', $this->editingCategoryId))
            ->exists();

        if ($duplicate) {
            $this->addError('slug', 'A category with this slug already exists. Slugs must be unique.');

            return;
        }

        if ($this->editingCategoryId !== null && $this->parentId === $this->editingCategoryId) {
            $this->addError('parentId', 'A category cannot be its own parent.');

            return;
        }

        $payload = [
            'name' => trim($this->name),
            'slug' => $slug,
            'tagline' => $this->tagline ?: null,
            'icon' => $this->icon ?: null,
            'image' => $this->image ?: null,
            'hero_color' => $this->heroColor,
            'booking_mode' => $this->bookingMode,
            'description' => $this->description ?: null,
            'seo_title' => $this->seoTitle ?: null,
            'seo_description' => $this->seoDescription ?: null,
            'parent_id' => $this->parentId,
        ];

        if ($this->isEditing && $this->editingCategoryId !== null) {
            $category = VendorCategory::query()->findOrFail($this->editingCategoryId);

            // Cycle guard: the chosen parent must not sit inside this
            // category's own subtree.
            if ($this->parentId !== null
                && in_array($this->parentId, $this->descendantIds($category), true)) {
                $this->addError('parentId', 'Cannot nest a category inside its own subcategory.');

                return;
            }

            $category->update($payload);
            $this->feedbackMessage = 'Category updated successfully.';
        } else {
            $nextSort = $this->parentId === null
                ? (int) VendorCategory::query()->whereNull('parent_id')->max('sort')
                : (int) VendorCategory::query()->where('parent_id', $this->parentId)->max('sort');

            $payload['sort'] = $nextSort + 1;
            $payload['is_active'] = true;

            VendorCategory::create($payload);
            $this->feedbackMessage = $this->parentId === null
                ? 'Top-level category created.'
                : 'Subcategory created.';
        }

        $this->closeModal();
    }

    /**
     * Enable/disable a category. Deactivation cascades to its subcategories
     * so the marketplace never shows a child of a hidden parent.
     */
    public function toggleCategory(int $categoryId): void
    {
        $this->assertPlatformAdmin();

        $category = VendorCategory::query()->find($categoryId);

        if (! $category) {
            abort(404);
        }

        $category->update(['is_active' => ! $category->is_active]);
        $newState = $category->fresh()->is_active;

        $affected = 1;
        if ($category->parent_id === null) {
            $affected += VendorCategory::query()
                ->where('parent_id', $category->id)
                ->update(['is_active' => $newState]);
        }

        $this->feedbackMessage = $newState
            ? 'Category enabled'.($affected > 1 ? " with {$affected} total rows active." : '.')
            : 'Category disabled'.($affected > 1 ? " — {$affected} rows (incl. subcategories) hidden." : '.');
    }

    /**
     * Move a category up/down among its siblings (reindexes sibling sorts).
     */
    public function moveCategory(int $categoryId, string $direction): void
    {
        $this->assertPlatformAdmin();

        if (! in_array($direction, ['up', 'down'], true)) {
            return;
        }

        $category = VendorCategory::query()->find($categoryId);

        if (! $category) {
            abort(404);
        }

        $siblings = VendorCategory::query()
            ->where('parent_id', $category->parent_id)
            ->orderBy('sort')
            ->orderBy('name')
            ->get(['id', 'sort']);

        $ids = $siblings->pluck('id')->values();
        $index = $ids->search(fn ($id) => (string) $id === (string) $category->id);

        if ($index === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($targetIndex < 0 || $targetIndex >= $ids->count()) {
            $this->errorMessage = 'Already at the '.($direction === 'up' ? 'top' : 'bottom').' of its group.';

            return;
        }

        $idsArray = $ids->toArray();
        [$idsArray[$index], $idsArray[$targetIndex]] = [$idsArray[$targetIndex], $idsArray[$index]];

        // Children are indexed inside their parent's sort band
        // (parentSort*100 + n) so flat category dropdowns stay grouped
        // parent → subcategories even after a manual reorder.
        $base = $category->parent_id !== null
            ? (int) VendorCategory::query()->where('id', $category->parent_id)->value('sort') * 100
            : 0;

        foreach ($idsArray as $position => $id) {
            VendorCategory::query()->where('id', $id)->update(['sort' => $base + $position + 1]);
        }

        $this->feedbackMessage = 'Order updated.';
    }

    /**
     * Delete only when the category is genuinely unused (no subcategories,
     * tenants, services, packages, RFQs or service bookings reference it).
     */
    public function deleteCategory(int $categoryId): void
    {
        $this->assertPlatformAdmin();

        $category = VendorCategory::query()->find($categoryId);

        if (! $category) {
            abort(404);
        }

        $reasons = [];

        if ($category->children()->exists()) {
            $reasons[] = 'it still has subcategories';
        }
        if ($category->tenants()->exists()) {
            $reasons[] = 'vendor businesses are assigned to it';
        }
        if ($category->services()->exists()) {
            $reasons[] = 'services are published under it';
        }

        $id = $category->id;
        if (DB::table('vendor_packages')->where('vendor_category_id', $id)->exists()) {
            $reasons[] = 'packages reference it';
        }
        if (DB::table('rfqs')->where('vendor_category_id', $id)->exists()) {
            $reasons[] = 'quote requests reference it';
        }
        if (DB::table('service_bookings')->where('vendor_category_id', $id)->exists()) {
            $reasons[] = 'service bookings reference it';
        }

        if ($reasons !== []) {
            $this->errorMessage = 'Cannot delete: '.implode(', ', $reasons).'. Deactivate it instead.';

            return;
        }

        $category->delete();
        $this->feedbackMessage = 'Category deleted.';
    }

    public function render()
    {
        $this->assertPlatformAdmin();

        return view('livewire.admin-category-manager', [
            'tree' => $this->tree,
        ])->layout('components.layouts.app', [
            'title' => 'Categories — Super Admin',
        ]);
    }
}
