<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VendorCategory;
use App\Models\VendorPortfolioMedia;
use App\Models\VendorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Enterprise expansion seed data: platform defaults + demo marketplace vendors
 * (photographer, caterer, decorator, makeup artist, planner, transport).
 * All PKR / Asia-Karachi oriented. Safe to re-run (updateOrCreate).
 */
class VendorEcosystemSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------------------------------------------
        // Platform settings (commission + defaults)
        // ---------------------------------------------------------------
        PlatformSetting::updateOrCreate(
            ['key' => 'marketplace.commission.default_rate'],
            ['value' => 0.05, 'type' => 'scalar', 'description' => 'Default platform commission (fraction of booking value, e.g. 0.05 = 5%)']
        );
        PlatformSetting::updateOrCreate(
            ['key' => 'marketplace.advance.default_percent'],
            ['value' => 30, 'type' => 'scalar', 'description' => 'Default advance payment percent to confirm a service booking']
        );

        $categories = VendorCategory::query()->pluck('id', 'slug');

        // ---------------------------------------------------------------
        // Demo vendor tenants across categories (approved + visible)
        // ---------------------------------------------------------------
        $vendors = [
            [
                'name' => 'Lens & Lantern Studio', 'slug' => 'lens-and-lantern-studio', 'category' => 'photography',
                'email' => 'studio@lenslantern.pk', 'phone' => '+92 321 5550110',
                'city' => 'Lahore', 'area' => 'DHA Phase 5', 'address' => 'Commercial Market, DHA Phase 5, Lahore',
                'owner' => 'Bilal Ahmed (Lead Photographer)', 'owner_email' => 'photographer@venuecloud.pk',
                'about' => 'Award-winning cinematic wedding photography team — 350+ weddings covered across Pakistan with same-day edit delivery.',
                'tagline' => 'Rang-e-riwayat, frame by frame',
                'services' => [
                    ['name' => 'Baraat Cinematic Coverage', 'pricing_type' => 'DAY', 'price_from' => 85000, 'price_to' => 150000, 'unit' => 'per event', 'highlights' => ['2 cinematographers + 1 photographer', 'Same-day teaser edit', 'Drone coverage included']],
                    ['name' => 'Mehndi Highlight Package', 'pricing_type' => 'DAY', 'price_from' => 45000, 'price_to' => 75000, 'unit' => 'per event', 'highlights' => ['Festive color grading', 'Next-morning delivery']],
                    ['name' => 'Pre-Wedding Couple Shoot', 'pricing_type' => 'HOURLY', 'price_from' => 18000, 'price_to' => 35000, 'unit' => 'per session', 'highlights' => ['2 hour outdoor session', '20 retouched photos']],
                ],
                'media' => [
                    ['https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1200&auto=format&fit=crop', 'Baraat stage'],
                    ['https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=1200&auto=format&fit=crop', 'Mehndi night'],
                    ['https://images.unsplash.com/photo-1465495976277-4387d4b0b4c6?q=80&w=1200&auto=format&fit=crop', 'Couple portrait'],
                ],
            ],
            [
                'name' => 'Nawab Caterers', 'slug' => 'nawab-caterers', 'category' => 'catering',
                'email' => 'orders@nawabcaterers.pk', 'phone' => '+92 333 4440990',
                'city' => 'Karachi', 'area' => 'PECHS Block 2', 'address' => 'Shahrah-e-Faisal, PECHS, Karachi',
                'owner' => 'Ustad Rafiq Nawab (Executive Chef)', 'owner_email' => 'caterer@venuecloud.pk',
                'about' => 'Three generations of Mughlai catering mastery — live food stations, continental counters and traditional desi feasts for up to 2000 guests.',
                'tagline' => 'Zaika shahi, service khaas',
                'services' => [
                    ['name' => 'Royal Walima Feast', 'pricing_type' => 'PER_HEAD', 'price_from' => 2200, 'price_to' => 3800, 'unit' => 'per head', 'highlights' => ['Mutton dum pukht', 'Live BBQ counter', 'Service staff included']],
                    ['name' => 'Corporate Lunch Box', 'pricing_type' => 'PER_HEAD', 'price_from' => 850, 'price_to' => 1500, 'unit' => 'per head', 'highlights' => ['Hygienic sealed boxes', 'On-time delivery']],
                ],
                'media' => [
                    ['https://images.unsplash.com/photo-1555244162-803834f70033?q=80&w=1200&auto=format&fit=crop', 'Live BBQ station'],
                    ['https://images.unsplash.com/photo-1414235077428-338989a2e8c0?q=80&w=1200&auto=format&fit=crop', 'Continental counter'],
                ],
            ],
            [
                'name' => 'Gulshan Decor House', 'slug' => 'gulshan-decor-house', 'category' => 'decoration',
                'email' => 'design@gulshandecor.pk', 'phone' => '+92 300 3330770',
                'city' => 'Islamabad', 'area' => 'F-7 Markaz', 'address' => 'Jinnah Super Market, F-7, Islamabad',
                'owner' => 'Ayesha Gulshan (Creative Director)', 'owner_email' => 'decorator@venuecloud.pk',
                'about' => 'Bespoke wedding stages, floral architecture and luxury lighting design — theme boards approved by you before setup day.',
                'tagline' => 'Design dil se, setup shaan se',
                'services' => [
                    ['name' => 'Signature Baraat Stage', 'pricing_type' => 'PROJECT', 'price_from' => 180000, 'price_to' => 450000, 'unit' => 'per project', 'highlights' => ['Fresh floral backdrop', 'LED pin-spot lighting', 'Entrance arch']],
                    ['name' => 'Mehndi Theme Setup', 'pricing_type' => 'PROJECT', 'price_from' => 75000, 'price_to' => 160000, 'unit' => 'per project', 'highlights' => ['Marigold drapes', 'Swing seating', 'Fairy-light canopy']],
                ],
                'media' => [
                    ['https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1200&auto=format&fit=crop', 'Royal stage'],
                    ['https://images.unsplash.com/photo-1478146896981-b80fe463b330?q=80&w=1200&auto=format&fit=crop', 'Floral wall'],
                ],
            ],
            [
                'name' => 'Noor Bridal Lounge', 'slug' => 'noor-bridal-lounge', 'category' => 'beauty-bridal',
                'email' => 'booking@noorbridal.pk', 'phone' => '+92 335 7770220',
                'city' => 'Lahore', 'area' => 'Gulberg III', 'address' => 'MM Alam Road, Gulberg III, Lahore',
                'owner' => 'Sana Noor (Celebrity Makeup Artist)', 'owner_email' => 'makeup@venuecloud.pk',
                'about' => 'HD airbrush bridal makeup, mehndi artistry and groom styling by a certified team — trials before your big day.',
                'tagline' => 'Noorat ka naghma',
                'services' => [
                    ['name' => 'Signature Bridal Makeup', 'pricing_type' => 'APPOINTMENT', 'price_from' => 45000, 'price_to' => 120000, 'unit' => 'per bride', 'highlights' => ['HD airbrush', 'Hair + dupatta setting', 'On-location available']],
                    ['name' => 'Mehndi Guest Makeup', 'pricing_type' => 'APPOINTMENT', 'price_from' => 8000, 'price_to' => 18000, 'unit' => 'per person', 'highlights' => ['Soft glam look', '45 minute session']],
                ],
                'media' => [
                    ['https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?q=80&w=1200&auto=format&fit=crop', 'Bridal suite'],
                    ['https://images.unsplash.com/photo-1487412947147-5cebf100ffc2?q=80&w=1200&auto=format&fit=crop', 'Makeup artistry'],
                ],
            ],
            [
                'name' => 'Jashan Events Co.', 'slug' => 'jashan-events-co', 'category' => 'wedding-planners',
                'email' => 'plan@jashanevents.pk', 'phone' => '+92 310 1110550',
                'city' => 'Islamabad', 'area' => 'Blue Area', 'address' => 'Jinnah Avenue, Blue Area, Islamabad',
                'owner' => 'Faisal Jashan (Event Director)', 'owner_email' => 'planner@venuecloud.pk',
                'about' => 'Full-service wedding planning — budgets, vendors, timelines and guest logistics handled end-to-end.',
                'tagline' => 'Jashan ehtiyaat se, yaadon ke liye',
                'services' => [
                    ['name' => 'Complete Wedding Management', 'pricing_type' => 'PROJECT', 'price_from' => 250000, 'price_to' => 800000, 'unit' => 'per wedding', 'highlights' => ['Vendor negotiation', 'Day-of coordination team', 'Budget tracking']],
                    ['name' => 'Destination Wedding Planning', 'pricing_type' => 'CONSULT', 'price_from' => 150000, 'price_to' => null, 'unit' => 'per event', 'highlights' => ['Venue scouting', 'Travel logistics']],
                ],
                'media' => [
                    ['https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?q=80&w=1200&auto=format&fit=crop', 'Lawn ceremony'],
                ],
            ],
            [
                'name' => 'Shahi Fleet Rentals', 'slug' => 'shahi-fleet-rentals', 'category' => 'transport',
                'email' => 'ride@shahifleet.pk', 'phone' => '+92 344 8880330',
                'city' => 'Karachi', 'area' => 'Clifton', 'address' => 'Khayaban-e-Bukhari, Clifton, Karachi',
                'owner' => 'Kamran Shahi (Fleet Manager)', 'owner_email' => 'transport@venuecloud.pk',
                'about' => 'Chauffeur-driven luxury fleet — bridal cars, coasters and vintage rides with uniformed chauffeurs.',
                'tagline' => 'Dulhan ki dulaan, shaan ke saath',
                'services' => [
                    ['name' => 'Bridal Car Package', 'pricing_type' => 'DAY', 'price_from' => 25000, 'price_to' => 80000, 'unit' => 'per day', 'highlights' => ['Floral decor included', 'Uniformed chauffeur']],
                    ['name' => 'Guest Coaster (AC)', 'pricing_type' => 'DAY', 'price_from' => 35000, 'price_to' => 55000, 'unit' => 'per day', 'highlights' => ['30 seats', 'Karachi-wide service']],
                ],
                'media' => [
                    ['https://images.unsplash.com/photo-1563720223185-11003d516935?q=80&w=1200&auto=format&fit=crop', 'Luxury fleet'],
                ],
            ],
        ];

        foreach ($vendors as $spec) {
            $category = $categories[$spec['category']];

            $owner = User::withTrashed()->updateOrCreate(
                ['email' => $spec['owner_email']],
                [
                    'name' => $spec['owner'],
                    'role' => 'ROLE_TENANT_OWNER',
                    'password' => Hash::make('password'),
                    'status' => 'ACTIVE',
                ]
            );
            $owner->forceFill(['deleted_at' => null])->save();
            $owner->assignRole('ROLE_TENANT_OWNER');

            $tenant = Tenant::updateOrCreate(
                ['slug' => $spec['slug']],
                [
                    'name' => $spec['name'],
                    'subdomain' => str_replace('-', '', $spec['slug']),
                    'email' => $spec['email'],
                    'phone' => $spec['phone'],
                    'address' => $spec['address'],
                    'city' => $spec['city'],
                    'province' => 'Punjab',
                    'vendor_category_id' => $category,
                    'business_type' => strtoupper(str_replace('-', '_', $spec['category'])),
                    'about' => $spec['about'],
                    'service_areas' => [$spec['city']],
                    'currency' => 'PKR',
                    'verification_status' => 'APPROVED',
                    'verified_at' => now(),
                    'onboarding_completed_at' => now(),
                    'marketplace_visible' => true,
                    'subscription_plan' => 'STARTER',
                    'subscription_status' => 'ACTIVE',
                    'subscription_starts_at' => now()->subDays(30),
                    'subscription_expires_at' => now()->addMonths(11),
                    'is_active' => true,
                    'rating_avg' => 4.60,
                    'rating_count' => 12,
                ]
            );

            // Link the vendor owner user to their business (users.tenant_id).
            $owner->forceFill(['tenant_id' => $tenant->id])->save();

            foreach ($spec['services'] as $i => $service) {
                VendorService::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'name' => $service['name']],
                    [
                        'vendor_category_id' => $category,
                        'description' => $service['name'].' — professional service by '.$spec['name'].'.',
                        'pricing_type' => $service['pricing_type'],
                        'price_from' => $service['price_from'],
                        'price_to' => $service['price_to'],
                        'unit' => $service['unit'],
                        'highlights' => $service['highlights'],
                        'is_active' => true,
                        'sort' => $i,
                    ]
                );
            }

            foreach ($spec['media'] as $i => $media) {
                VendorPortfolioMedia::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'path' => $media[0]],
                    ['type' => 'IMAGE', 'caption' => $media[1], 'sort' => $i]
                );
            }
        }

        // Featured flagship hall tenant → map to the wedding-halls category
        $hallTenant = Tenant::query()->where('slug', 'venue-cloud')->first();
        if ($hallTenant) {
            $hallTenant->forceFill([
                'vendor_category_id' => $categories['wedding-halls'],
                'business_type' => 'WEDDING_HALLS',
                'about' => 'Flagship luxury wedding banqueting complex — single-host shift exclusivity, Mughlai catering and valet service.',
                'marketplace_visible' => true,
                'onboarding_completed_at' => $hallTenant->onboarding_completed_at ?? now(),
                'verified_at' => $hallTenant->verified_at ?? now(),
                'service_areas' => ['Lahore'],
            ])->save();
        }

        $this->command?->info('Vendor ecosystem seeded: '.count($vendors).' marketplace vendors + platform settings.');
    }
}
