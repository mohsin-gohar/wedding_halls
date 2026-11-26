<?php

namespace App\Livewire;

use App\Models\Hall;
use App\Models\Tenant;
use App\Models\Venue;
use App\Support\Media;
use App\Support\PermissionMatrix;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class HallMediaManager extends Component
{
    use WithFileUploads;

    public ?Hall $hall = null;

    public string $selectedHallId = '';

    // Hall Details
    public string $name = '';

    public string $tagline = '';

    public string $description = '';

    public string $operating_hours = '';

    public string $policies = '';

    public int $max_capacity = 500;

    public int $min_capacity = 150;

    public float $base_rent = 150000;

    public bool $has_air_conditioning = true;

    public bool $has_generator_backup = true;

    public bool $has_bridal_room = true;

    public bool $is_active = true;

    public string $cover_image = '';

    // Services & Product Catalog (White-Label Storefront Features)
    public array $services = [];

    public string $newServiceTitle = '';

    public float $newServicePrice = 0;

    public bool $newServiceComplimentary = false;

    public string $newServiceCategory = 'Decor & Ambience';

    public string $newServiceDescription = '';

    public string $newServiceImage = '';

    public string $newServiceIcon = 'sparkles';

    // Venue / Complex Details (White-Label Branding)
    public string $venue_name = '';

    public string $city = 'Lahore';

    public string $address = '';

    public string $phone = '';

    // Venue Location (Latitude / Longitude / Google Maps)
    public string $latitude = '';

    public string $longitude = '';

    public string $google_maps_url = '';

    // Gallery Media
    public array $images = [];

    public array $videos = [];

    // Local file uploads (mobile/laptop)
    public $imageUploads = [];

    public $videoUploads = [];

    // Website Builder & Section Visibility Customization
    public array $website_settings = [
        'announcement_banner' => 'Now accepting Winter 2026 wedding reservations with complimentary VIP bridal suite!',
        'show_announcement' => true,
        'show_video_tour' => true,
        'show_services' => true,
        'show_packages' => true,
        'show_shift_matrix' => true,
        'show_faqs' => true,
        'show_reviews' => true,
        'faqs' => [],
        'reviews' => [],
    ];

    // FAQ & Review input states
    public string $newFaqQuestion = '';

    public string $newFaqAnswer = '';

    public string $newReviewAuthor = '';

    public string $newReviewEvent = 'Walima Reception';

    public int $newReviewRating = 5;

    public string $newReviewQuote = '';

    // Inputs for adding new items
    public string $newImageUrl = '';

    public string $newVideoUrl = '';

    // New Hall Creation Modal
    public bool $showCreateModal = false;

    public string $newHallName = '';

    public int $newHallCapacity = 500;

    public float $newHallRent = 150000;

    protected $rules = [
        'name' => 'required|string|min:3|max:120',
        'venue_name' => 'nullable|string|min:3|max:120',
        'city' => 'required|string|max:80',
        'address' => 'nullable|string|max:255',
        'phone' => 'nullable|string|max:50',
        'latitude' => 'nullable|numeric|between:-90,90',
        'longitude' => 'nullable|numeric|between:-180,180',
        'google_maps_url' => 'nullable|url|max:500',
        'description' => 'nullable|string|max:1500',
        'max_capacity' => 'required|integer|min:50|max:10000',
        'min_capacity' => 'required|integer|min:20|max:10000',
        'base_rent' => 'required|numeric|min:10000',
        'has_air_conditioning' => 'boolean',
        'has_generator_backup' => 'boolean',
        'has_bridal_room' => 'boolean',
        'is_active' => 'boolean',
        'cover_image' => 'nullable|url',
    ];

    /**
     * Central permission guards for the hall website editor. The route allows
     * managers to view the editor, but only principals granted the specific
     * management capability may persist changes or write uploaded files.
     */
    protected function assertCanManageHall(): void
    {
        abort_unless(
            PermissionMatrix::userCan(Auth::user(), 'business.update'),
            403,
            'You do not have permission to edit hall and website settings.'
        );
    }

    protected function assertCanManageServices(): void
    {
        abort_unless(
            PermissionMatrix::userCan(Auth::user(), 'services.manage'),
            403,
            'You do not have permission to manage the service catalogue.'
        );
    }

    public function mount()
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->isSuperAdmin() && (! $user->tenant_id || $user->isCustomer())) {
            abort(403, 'Unauthorized. Tenant staff access required.');
        }

        // Retrieve halls strictly scoped to the user's tenant
        $firstHall = Hall::where('tenant_id', $user->tenant_id)->first();

        // If no hall exists for this tenant yet, auto-provision initial hall
        if (! $firstHall && $user->tenant_id) {
            $venue = Venue::where('tenant_id', $user->tenant_id)->first();
            if (! $venue) {
                $venue = Venue::create([
                    'tenant_id' => $user->tenant_id,
                    'name' => ($user->tenant->name ?? 'Venue Complex'),
                    'slug' => Str::slug(($user->tenant->name ?? 'venue').'-'.Str::random(4)),
                    'city' => $user->tenant->city ?? 'Lahore',
                    'address' => $user->tenant->address ?? 'Main Boulevard, Gulberg III, Lahore',
                    'phone' => $user->tenant->phone ?? '+92 300 1234567',
                    'is_active' => true,
                ]);
            }

            $firstHall = Hall::create([
                'tenant_id' => $user->tenant_id,
                'venue_id' => $venue->id,
                'name' => ($user->tenant->name ?? 'Royal Grand Hall'),
                'min_capacity' => 150,
                'max_capacity' => 500,
                'base_rent' => 150000.00,
                'description' => 'Luxury wedding banquet hall with central AC and heavy generator backup.',
                'cover_image' => null,
                'images' => [],
                'has_air_conditioning' => true,
                'has_generator_backup' => true,
                'has_bridal_room' => true,
                'is_active' => true,
            ]);
        }

        if ($firstHall) {
            $this->loadHall($firstHall);
        }
    }

    public function switchHall(string $hallId)
    {
        $user = Auth::user();
        $hall = Hall::where('tenant_id', $user->tenant_id)->findOrFail($hallId);
        $this->loadHall($hall);
    }

    private function loadHall(Hall $hall)
    {
        $this->hall = $hall;
        $this->selectedHallId = $hall->id;
        $this->name = $hall->name ?? '';
        $this->tagline = $hall->tagline ?? '';
        $this->description = $hall->description ?? '';
        $this->operating_hours = $hall->operating_hours ?? '';
        $this->policies = $hall->policies ?? '';
        $this->max_capacity = $hall->max_capacity ?? 500;
        $this->min_capacity = $hall->min_capacity ?? 150;
        $this->base_rent = (float) ($hall->base_rent ?? 150000);
        $this->has_air_conditioning = (bool) $hall->has_air_conditioning;
        $this->has_generator_backup = (bool) $hall->has_generator_backup;
        $this->has_bridal_room = (bool) $hall->has_bridal_room;
        $this->is_active = (bool) $hall->is_active;
        $this->cover_image = $hall->cover_image ?? '';
        $this->services = is_array($hall->services) ? $hall->services : [];
        $this->images = is_array($hall->images) ? $hall->images : [];
        $this->videos = is_array($hall->videos) ? $hall->videos : [];

        $savedSettings = is_array($hall->website_settings) ? $hall->website_settings : [];
        $this->website_settings = array_merge([
            'announcement_banner' => 'Now accepting Winter 2026 wedding reservations with complimentary VIP bridal suite!',
            'show_announcement' => true,
            'show_video_tour' => true,
            'show_services' => true,
            'show_packages' => true,
            'show_shift_matrix' => true,
            'show_faqs' => true,
            'show_reviews' => true,
            'faqs' => [],
            'reviews' => [],
        ], $savedSettings);

        // Venue details
        $venue = $hall->venue;
        if ($venue) {
            $this->venue_name = $venue->name ?? '';
            $this->city = $venue->city ?? 'Lahore';
            $this->address = $venue->address ?? '';
            $this->phone = $hall->getWhatsappNumber() ?? $venue->phone ?? '';
            $this->latitude = (string) ($venue->latitude ?? '');
            $this->longitude = (string) ($venue->longitude ?? '');
            $this->google_maps_url = $venue->google_maps_url ?? '';
        } else {
            $this->venue_name = Auth::user()->tenant->name ?? 'Venue Complex';
            $this->city = Auth::user()->tenant->city ?? 'Lahore';
            $this->address = Auth::user()->tenant->address ?? '';
            $this->phone = $hall->getWhatsappNumber() ?? Auth::user()->tenant->phone ?? '';
            $this->latitude = '';
            $this->longitude = '';
            $this->google_maps_url = '';
        }
    }

    public function openCreateModal()
    {
        $tenant = Auth::user()->tenant;
        if ($tenant && ! $tenant->canAddHall()) {
            session()->flash('error', 'Hall limit reached ('.$tenant->halls()->count().'/'.($tenant->subscriptionPlan->max_halls ?? 1).'). Please upgrade your SaaS subscription to add more banquet halls.');

            return;
        }

        $this->newHallName = '';
        $this->newHallCapacity = 500;
        $this->newHallRent = 150000;
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
    }

    public function createNewHall()
    {
        $this->assertCanManageHall();
        abort_unless(PermissionMatrix::userCan(Auth::user(), 'locations.manage'), 403);

        $tenant = Auth::user()->tenant;
        if ($tenant && ! $tenant->canAddHall()) {
            session()->flash('error', 'Hall limit reached. Please upgrade your SaaS subscription.');
            $this->showCreateModal = false;

            return;
        }

        $this->validate([
            'newHallName' => 'required|string|min:3|max:120',
            'newHallCapacity' => 'required|integer|min:50|max:10000',
            'newHallRent' => 'required|numeric|min:10000',
        ]);

        $user = Auth::user();
        if (! $user || (! $user->isSuperAdmin() && ! $user->tenant_id)) {
            abort(403, 'Tenant required to create hall.');
        }
        $venue = Venue::where('tenant_id', $user->tenant_id)->first();
        if (! $venue) {
            $venue = Venue::create([
                'tenant_id' => $user->tenant_id,
                'name' => ($user->tenant->name ?? 'Venue Complex'),
                'slug' => Str::slug(($user->tenant->name ?? 'venue').'-'.Str::random(4)),
                'city' => $user->tenant->city ?? 'Lahore',
                'address' => $user->tenant->address ?? 'Main Boulevard, Gulberg III, Lahore',
                'phone' => $user->tenant->phone ?? '+92 300 1234567',
                'is_active' => true,
            ]);
        }

        $newHall = Hall::create([
            'tenant_id' => $user->tenant_id,
            'venue_id' => $venue->id,
            'name' => $this->newHallName,
            'min_capacity' => (int) ($this->newHallCapacity * 0.4),
            'max_capacity' => $this->newHallCapacity,
            'base_rent' => $this->newHallRent,
            'description' => 'Spectacular wedding banquet marquee ballroom.',
            'cover_image' => null,
            'images' => [],
            'has_air_conditioning' => true,
            'has_generator_backup' => true,
            'has_bridal_room' => true,
            'is_active' => true,
        ]);

        $this->showCreateModal = false;
        $this->loadHall($newHall);
        session()->flash('message', "New hall [{$newHall->name}] created successfully! You can now configure its photos and pricing.");
    }

    public function addImage()
    {
        $this->validate([
            'newImageUrl' => 'required|url',
        ]);

        $this->images[] = trim($this->newImageUrl);
        if (empty($this->cover_image)) {
            $this->cover_image = trim($this->newImageUrl);
        }
        $this->newImageUrl = '';
        $this->save();
        session()->flash('message', 'New photo added successfully!');
    }

    public function removeImage(int $index)
    {
        if (isset($this->images[$index])) {
            $removedUrl = $this->images[$index];
            unset($this->images[$index]);
            $this->images = array_values($this->images);

            if ($this->cover_image === $removedUrl) {
                $this->cover_image = $this->images[0] ?? '';
            }

            $this->save();
            session()->flash('message', 'Photo removed.');
        }
    }

    public function setCoverImage(string $url)
    {
        $this->cover_image = $url;
        $this->save();
        session()->flash('message', 'Cover image updated!');
    }

    public function addVideo()
    {
        $this->validate([
            'newVideoUrl' => 'required|url',
        ]);

        $url = trim($this->newVideoUrl);

        // Support YouTube, Vimeo, Dailymotion, direct files
        $url = Media::videoEmbedUrl($url);

        $this->videos[] = $url;
        $this->newVideoUrl = '';
        $this->save();
        session()->flash('message', 'Video walkthrough added successfully!');
    }

    public function removeVideo(int $index)
    {
        if (isset($this->videos[$index])) {
            unset($this->videos[$index]);
            $this->videos = array_values($this->videos);
            $this->save();
            session()->flash('message', 'Video removed.');
        }
    }

    public function uploadImages()
    {
        $this->assertCanManageHall();

        $this->validate([
            'imageUploads.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120', // 5MB per image
        ]);

        if (empty($this->imageUploads)) {
            session()->flash('error', 'Please select at least one image file.');

            return;
        }

        $tenantId = Auth::user()->tenant_id ?? 'platform';
        $hallId = $this->hall?->id ?? 'new';
        foreach ($this->imageUploads as $file) {
            $path = $file->store('halls/'.$tenantId.'/'.$hallId.'/images', 'public');
            $url = Storage::disk('public')->url($path);
            $this->images[] = $url;
            if (empty($this->cover_image)) {
                $this->cover_image = $url;
            }
        }
        $this->imageUploads = [];
        $this->save();
        session()->flash('message', 'Photos uploaded successfully!');
    }

    public function uploadVideos()
    {
        $this->assertCanManageHall();

        $this->validate([
            'videoUploads.*' => 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime|max:51200', // 50MB
        ]);

        if (empty($this->videoUploads)) {
            session()->flash('error', 'Please select at least one video file.');

            return;
        }

        $tenantId = Auth::user()->tenant_id ?? 'platform';
        $hallId = $this->hall?->id ?? 'new';
        foreach ($this->videoUploads as $file) {
            $path = $file->store('halls/'.$tenantId.'/'.$hallId.'/videos', 'public');
            $url = Storage::disk('public')->url($path);
            $this->videos[] = $url;
        }
        $this->videoUploads = [];
        $this->save();
        session()->flash('message', 'Videos uploaded successfully!');
    }

    public function save()
    {
        $this->assertCanManageHall();
        $this->validate();

        $user = Auth::user();

        if ($this->hall) {
            // Strictly check ownership unless super admin
            if (! $user->isSuperAdmin() && $this->hall->tenant_id !== $user->tenant_id) {
                abort(403, 'Unauthorized.');
            }

            $settings = is_array($this->website_settings) ? $this->website_settings : [];
            if (! empty($this->phone)) {
                $settings['whatsapp_number'] = $this->phone;
                $settings['phone'] = $this->phone;
            }

            $this->hall->update([
                'name' => $this->name,
                'tagline' => $this->tagline,
                'description' => $this->description,
                'operating_hours' => $this->operating_hours,
                'policies' => $this->policies,
                'max_capacity' => $this->max_capacity,
                'min_capacity' => $this->min_capacity,
                'base_rent' => $this->base_rent,
                'has_air_conditioning' => $this->has_air_conditioning,
                'has_generator_backup' => $this->has_generator_backup,
                'has_bridal_room' => $this->has_bridal_room,
                'is_active' => $this->is_active,
                'cover_image' => $this->cover_image,
                'services' => array_values($this->services),
                'website_settings' => $settings,
                'images' => array_values($this->images),
                'videos' => array_values($this->videos),
            ]);

            // Update venue record as well
            if ($this->hall->venue) {
                $this->hall->venue->update([
                    'name' => ! empty($this->venue_name) ? $this->venue_name : $this->name,
                    'city' => $this->city,
                    'address' => $this->address,
                    'phone' => $this->phone,
                    'latitude' => $this->latitude ?: null,
                    'longitude' => $this->longitude ?: null,
                    'google_maps_url' => $this->google_maps_url ?: null,
                ]);
            }

            // Update tenant name
            $targetTenant = $this->hall->tenant ?: $user->tenant;
            if ($targetTenant) {
                $targetTenant->update([
                    'name' => $this->venue_name,
                    'city' => $this->city,
                    'address' => $this->address,
                    'phone' => $this->phone,
                ]);
            }

            session()->flash('message', "Hall & Venue [{$this->name}] updated successfully! Live website reflects all changes.");
        }
    }

    public function deleteHall(string $hallId)
    {
        $this->assertCanManageHall();
        abort_unless(PermissionMatrix::userCan(Auth::user(), 'locations.manage'), 403);

        $user = Auth::user();

        if (! $user?->tenant_id) {
            abort(403, 'A tenant account is required to delete a hall.');
        }

        $result = DB::transaction(function () use ($user, $hallId): array {
            // Use the same tenant → hall lock order as BookingService so a
            // concurrent reservation cannot slip between the checks and delete.
            $tenant = Tenant::query()->whereKey($user->tenant_id)->lockForUpdate()->firstOrFail();
            $hall = Hall::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey($hallId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($tenant->halls()->count() <= 1) {
                return ['error' => 'You must maintain at least one active hall space for your venue.'];
            }

            $hall->loadCount(['bookings', 'inquiries', 'floorPlans', 'hallViews', 'hallWishlists', 'reviews']);

            if (
                $hall->bookings_count > 0
                || $hall->inquiries_count > 0
                || $hall->floor_plans_count > 0
                || $hall->hall_views_count > 0
                || $hall->hall_wishlists_count > 0
                || $hall->reviews_count > 0
            ) {
                return ['error' => "Cannot delete [{$hall->name}] because it has booking or marketplace history. Keep the hall and disable its public listing instead."];
            }

            $name = $hall->name;
            $hall->delete();

            return ['name' => $name];
        });

        if (isset($result['error'])) {
            session()->flash('error', $result['error']);

            return;
        }

        $remaining = Hall::query()->where('tenant_id', $user->tenant_id)->first();
        if ($remaining) {
            $this->loadHall($remaining);
        }

        session()->flash('message', "Hall [{$result['name']}] deleted successfully.");
    }

    public function addService()
    {
        $this->assertCanManageServices();

        $this->validate([
            'newServiceTitle' => 'required|string|min:3|max:100',
            'newServicePrice' => 'required|numeric|min:0',
            'newServiceCategory' => 'required|string|max:60',
        ]);

        $imageUrl = trim($this->newServiceImage);
        if (empty($imageUrl)) {
            $imageUrl = Hall::resolveServiceImage([
                'title' => $this->newServiceTitle,
                'category' => $this->newServiceCategory,
                'description' => $this->newServiceDescription,
                'icon' => $this->newServiceIcon,
            ]);
        }

        $this->services[] = [
            'title' => trim($this->newServiceTitle),
            'price' => (float) $this->newServicePrice,
            'is_complimentary' => (bool) $this->newServiceComplimentary,
            'category' => trim($this->newServiceCategory),
            'description' => trim($this->newServiceDescription),
            'image' => $imageUrl,
            'icon' => $this->newServiceIcon ?: 'sparkles',
        ];

        // Reset service form fields
        $this->newServiceTitle = '';
        $this->newServicePrice = 0;
        $this->newServiceComplimentary = false;
        $this->newServiceCategory = 'Decor & Ambience';
        $this->newServiceDescription = '';
        $this->newServiceImage = '';
        $this->newServiceIcon = 'sparkles';

        $this->save();
        session()->flash('message', 'Service / Product added to your live website catalog!');
    }

    public function removeService(int $index)
    {
        $this->assertCanManageServices();

        if (isset($this->services[$index])) {
            unset($this->services[$index]);
            $this->services = array_values($this->services);
            $this->save();
            session()->flash('message', 'Service removed from catalog.');
        }
    }

    public function toggleServiceComplimentary(int $index)
    {
        $this->assertCanManageServices();

        if (isset($this->services[$index])) {
            $this->services[$index]['is_complimentary'] = ! ($this->services[$index]['is_complimentary'] ?? false);
            $this->save();
            session()->flash('message', 'Service status updated.');
        }
    }

    public function addFaq()
    {
        $this->validate([
            'newFaqQuestion' => 'required|string|min:4|max:200',
            'newFaqAnswer' => 'required|string|min:5|max:1000',
        ]);

        $faqs = $this->website_settings['faqs'] ?? [];
        $faqs[] = [
            'question' => trim($this->newFaqQuestion),
            'answer' => trim($this->newFaqAnswer),
        ];

        $this->website_settings['faqs'] = $faqs;
        $this->newFaqQuestion = '';
        $this->newFaqAnswer = '';
        $this->save();
        session()->flash('message', 'FAQ item added to your website!');
    }

    public function removeFaq(int $index)
    {
        if (isset($this->website_settings['faqs'][$index])) {
            unset($this->website_settings['faqs'][$index]);
            $this->website_settings['faqs'] = array_values($this->website_settings['faqs']);
            $this->save();
            session()->flash('message', 'FAQ item removed.');
        }
    }

    public function addReview()
    {
        $this->validate([
            'newReviewAuthor' => 'required|string|min:3|max:100',
            'newReviewQuote' => 'required|string|min:5|max:500',
        ]);

        $reviews = $this->website_settings['reviews'] ?? [];
        $reviews[] = [
            'author' => trim($this->newReviewAuthor),
            'event' => trim($this->newReviewEvent),
            'rating' => (int) $this->newReviewRating,
            'quote' => trim($this->newReviewQuote),
            'date' => Carbon::now()->format('d M Y'),
        ];

        $this->website_settings['reviews'] = $reviews;
        $this->newReviewAuthor = '';
        $this->newReviewQuote = '';
        $this->save();
        session()->flash('message', 'Client testimonial added to your website!');
    }

    public function removeReview(int $index)
    {
        if (isset($this->website_settings['reviews'][$index])) {
            unset($this->website_settings['reviews'][$index]);
            $this->website_settings['reviews'] = array_values($this->website_settings['reviews']);
            $this->save();
            session()->flash('message', 'Review testimonial removed.');
        }
    }

    public function render()
    {
        $user = Auth::user();
        $allHalls = $user && $user->tenant_id
            ? Hall::where('tenant_id', $user->tenant_id)->get()
            : collect([]);

        return view('livewire.hall-media-manager', [
            'allHalls' => $allHalls,
        ])->layout('components.layouts.app', ['title' => 'Hall Website & Brand Manager — Rent Admin']);
    }
}
