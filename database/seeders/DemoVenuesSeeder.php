<?php

namespace Database\Seeders;

use App\Models\Hall;
use App\Models\Package;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoVenuesSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure existing VenueCloud tenant is APPROVED and ACTIVE
        $venueCloud = Tenant::where('slug', 'venue-cloud')->first();
        if ($venueCloud) {
            $venueCloud->update([
                'verification_status' => 'APPROVED',
                'is_active' => true,
                'subscription_status' => 'ACTIVE',
                'subscription_expires_at' => Carbon::now()->addYear(),
            ]);
            Hall::where('tenant_id', $venueCloud->id)->update(['is_active' => true]);
            Venue::where('tenant_id', $venueCloud->id)->update(['is_active' => true]);
        }

        $plans = SubscriptionPlan::all();
        $starterPlan = $plans->where('slug', 'starter-marquee')->first() ?? $plans->first();
        $executivePlan = $plans->where('slug', 'executive-banquet-suite')->first() ?? $plans->first();
        $enterprisePlan = $plans->where('slug', 'royal-imperial-enterprise')->first() ?? $plans->last();

        $defaultServices = config('hall.default_services') ?: [
            [
                'title' => 'VIP Bridal Luxury Dressing Suite',
                'price' => 25000,
                'is_complimentary' => true,
                'category' => 'VIP Hospitality',
                'description' => 'Air-conditioned private suite with attached restroom, illuminated Hollywood makeup vanity, and dedicated dressing concierge.',
                'icon' => 'sparkles'
            ],
            [
                'title' => '500 kVA Standby Power Generator',
                'price' => 35000,
                'is_complimentary' => true,
                'category' => 'Infrastructure',
                'description' => 'Uninterrupted power system with automatic synchronization and fuel backup covering 100% chiller AC load.',
                'icon' => 'bolt'
            ],
            [
                'title' => 'Bespoke Fresh Rose Stage Decor & Walkway',
                'price' => 85000,
                'is_complimentary' => false,
                'category' => 'Decor & Ambience',
                'description' => 'Fresh imported gladiolus, red roses, golden filigree stage sofa, ambient pin spot lighting, and floral aisle runners.',
                'icon' => 'star'
            ],
            [
                'title' => 'Executive Chauffeur Valet Parking Service',
                'price' => 20000,
                'is_complimentary' => false,
                'category' => 'Guest Logistics',
                'description' => 'Dedicated 8-member uniformed valet team with electronic key logging and paved parking for 250+ cars.',
                'icon' => 'truck'
            ],
            [
                'title' => '4K Drone Aerial Cinematography & LED P3 Wall',
                'price' => 60000,
                'is_complimentary' => false,
                'category' => 'Multimedia & Video',
                'description' => 'Dual 16x10 ft P3 ultra-HD stage display screens with live dual-camera vision mixer for program broadcast.',
                'icon' => 'camera'
            ]
        ];

        // Demo Marquees Data
        $demoVenues = [
            [
                'tenant_name' => 'Pearl Continental Marquee Group',
                'slug' => 'pearl-continental-marquee',
                'email' => 'banquets@pc-karachi.com',
                'phone' => '+92 21 35685021',
                'city' => 'Karachi',
                'area' => 'Clifton / Club Road',
                'address' => 'Club Road, Opp. PIDC House, Civil Lines, Karachi',
                'plan' => $enterprisePlan,
                'venues' => [
                    [
                        'name' => 'Pearl Continental Grand Marquee Complex',
                        'slug' => 'pc-grand-marquee-karachi',
                        'halls' => [
                            [
                                'name' => 'The Emerald Grand Ballroom',
                                'tagline' => 'Karachis Most Exclusive Coastal Banqueting Destination',
                                'min_capacity' => 250,
                                'max_capacity' => 1200,
                                'base_rent' => 250000,
                                'description' => 'Overlooking the Arabian Sea breeze, The Emerald features 24-foot soaring ceilings, bespoke Italian chandeliers, and world-class 5-star continental and Mughlai catering.',
                                'cover_image' => 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1200&auto=format&fit=crop',
                                'images' => [
                                    'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1200&auto=format&fit=crop',
                                    'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=1200&auto=format&fit=crop',
                                    'https://images.unsplash.com/photo-1465495976277-4387d4b0b4c6?q=80&w=1200&auto=format&fit=crop',
                                ],
                            ],
                            [
                                'name' => 'Oceanic Sea-Breeze Lawn',
                                'tagline' => 'Manicured Open-Air Lawn for Dream Mehndi & Shendi Celebrations',
                                'min_capacity' => 300,
                                'max_capacity' => 1500,
                                'base_rent' => 280000,
                                'description' => 'Expansive manicured open lawn under the starry skies of Karachi, adorned with floral canopies, ambient festoon lighting, and live tandoor stations.',
                                'cover_image' => 'https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?q=80&w=1200&auto=format&fit=crop',
                                'images' => [
                                    'https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?q=80&w=1200&auto=format&fit=crop',
                                    'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=1200&auto=format&fit=crop',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'tenant_name' => 'Serena Margalla Hospitality',
                'slug' => 'serena-margalla-hospitality',
                'email' => 'events@serena-islamabad.com',
                'phone' => '+92 51 2874000',
                'city' => 'Islamabad',
                'area' => 'Club Road / Diplomatic Enclave',
                'address' => 'Khayaban-e-Suhrawardy, Sector G-5/1, Islamabad',
                'plan' => $enterprisePlan,
                'venues' => [
                    [
                        'name' => 'Serena Margalla Convention Complex',
                        'slug' => 'serena-margalla-complex-isb',
                        'halls' => [
                            [
                                'name' => 'Margalla Crystal Pavilion',
                                'tagline' => 'Majestic Panoramic Views of Margalla Hills in the Federal Capital',
                                'min_capacity' => 200,
                                'max_capacity' => 1000,
                                'base_rent' => 320000,
                                'description' => 'The ultimate VIP venue for elite diplomatic receptions and grand royal weddings. Features column-free crystal acoustics, dedicated diplomatic security, and private helicopter landing accessibility.',
                                'cover_image' => 'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=1200&auto=format&fit=crop',
                                'images' => [
                                    'https://images.unsplash.com/photo-1511795409834-ef04bbd61622?q=80&w=1200&auto=format&fit=crop',
                                    'https://images.unsplash.com/photo-1520854221256-17451cc331bf?q=80&w=1200&auto=format&fit=crop',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'tenant_name' => 'Falaknuma Palace Marquees',
                'slug' => 'falaknuma-palace-marquees',
                'email' => 'reservations@falaknumapalace.pk',
                'phone' => '+92 51 5567890',
                'city' => 'Rawalpindi',
                'area' => 'Peshawar Road / Westridge',
                'address' => 'Main Peshawar Road, Near Westridge III, Rawalpindi',
                'plan' => $executivePlan,
                'venues' => [
                    [
                        'name' => 'Falaknuma Royal Event Center',
                        'slug' => 'falaknuma-royal-rawalpindi',
                        'halls' => [
                            [
                                'name' => 'The Falaknuma Grand Ballroom',
                                'tagline' => 'Imperial Mughal Architectural Splendor for Royal Barat & Walima',
                                'min_capacity' => 150,
                                'max_capacity' => 800,
                                'base_rent' => 180000,
                                'description' => 'Exquisite traditional arches, intricate mirror work (Aina Kari), and regal gold filigree detailing make Falaknuma the premier choice in the Twin Cities.',
                                'cover_image' => 'https://images.unsplash.com/photo-1545232979-fbf6c1619a91?q=80&w=1200&auto=format&fit=crop',
                                'images' => [
                                    'https://images.unsplash.com/photo-1545232979-fbf6c1619a91?q=80&w=1200&auto=format&fit=crop',
                                    'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1200&auto=format&fit=crop',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'tenant_name' => 'Dynasty Royal Banquets',
                'slug' => 'dynasty-royal-banquets',
                'email' => 'contact@dynastybanquets.pk',
                'phone' => '+92 41 8541234',
                'city' => 'Faisalabad',
                'area' => 'Canal Road / Kohinoor',
                'address' => 'Canal Expressway, Near Kohinoor City, Faisalabad',
                'plan' => $executivePlan,
                'venues' => [
                    [
                        'name' => 'Dynasty Banqueting Palace',
                        'slug' => 'dynasty-palace-faisalabad',
                        'halls' => [
                            [
                                'name' => 'The Dynasty Imperial Marquee',
                                'tagline' => 'Faisalabads Premier Luxury Wedding Destination with Air Conditioning',
                                'min_capacity' => 200,
                                'max_capacity' => 950,
                                'base_rent' => 160000,
                                'description' => 'Featuring massive climate-controlled German marquee structures, central audio-visual systems, illuminated fountains, and parking for 300 vehicles.',
                                'cover_image' => 'https://images.unsplash.com/photo-1532712938310-34cb3982ef74?q=80&w=1200&auto=format&fit=crop',
                                'images' => [
                                    'https://images.unsplash.com/photo-1532712938310-34cb3982ef74?q=80&w=1200&auto=format&fit=crop',
                                    'https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?q=80&w=1200&auto=format&fit=crop',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'tenant_name' => 'Mughal-e-Azam Grand Marquee',
                'slug' => 'mughal-e-azam-grand-marquee',
                'email' => 'events@mughaleazam.pk',
                'phone' => '+92 42 35914567',
                'city' => 'Lahore',
                'area' => 'Canal Road / Thokar',
                'address' => 'Main Canal Bank Road, Near Hanjarwal, Lahore',
                'plan' => $enterprisePlan,
                'venues' => [
                    [
                        'name' => 'Mughal-e-Azam Cultural Banquet Complex',
                        'slug' => 'mughal-e-azam-complex-lhr',
                        'halls' => [
                            [
                                'name' => 'Shah Jahan Royal Pavilion',
                                'tagline' => 'Authentic Mughal Heritage & Royal Celebration Ambience',
                                'min_capacity' => 300,
                                'max_capacity' => 1400,
                                'base_rent' => 210000,
                                'description' => 'Inspired by the Shahi Qila of Lahore, featuring hand-carved jharokas, royal brass lanterns, and legendary live charcoal barbecue setups.',
                                'cover_image' => 'https://images.unsplash.com/photo-1520854221256-17451cc331bf?q=80&w=1200&auto=format&fit=crop',
                                'images' => [
                                    'https://images.unsplash.com/photo-1520854221256-17451cc331bf?q=80&w=1200&auto=format&fit=crop',
                                    'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1200&auto=format&fit=crop',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($demoVenues as $vData) {
            $tenant = Tenant::firstOrCreate(
                ['slug' => $vData['slug']],
                [
                    'name' => $vData['tenant_name'],
                    'subdomain' => Str::slug($vData['tenant_name']),
                    'email' => $vData['email'],
                    'phone' => $vData['phone'],
                    'city' => $vData['city'],
                    'address' => $vData['address'],
                    'subscription_plan_id' => $vData['plan'] ? $vData['plan']->id : null,
                    'subscription_status' => 'ACTIVE',
                    'billing_cycle' => 'YEARLY',
                    'subscription_starts_at' => Carbon::now()->subMonths(1),
                    'subscription_expires_at' => Carbon::now()->addMonths(11),
                    'verification_status' => 'APPROVED',
                    'currency' => 'PKR',
                    'is_active' => true,
                ]
            );

            // Create Manager User
            $ownerEmail = 'owner@' . $vData['slug'] . '.pk';
            $user = User::firstOrCreate(
                ['email' => $ownerEmail],
                [
                    'tenant_id' => $tenant->id,
                    'name' => $vData['tenant_name'] . ' Owner',
                    'phone' => $vData['phone'],
                    'password' => Hash::make('password'),
                    'role' => 'ROLE_TENANT_OWNER',
                ]
            );
            if (method_exists($user, 'assignRole')) {
                $user->assignRole('ROLE_TENANT_OWNER');
            }

            foreach ($vData['venues'] as $venueItem) {
                $venue = Venue::firstOrCreate(
                    ['slug' => $venueItem['slug']],
                    [
                        'tenant_id' => $tenant->id,
                        'name' => $venueItem['name'],
                        'city' => $vData['city'],
                        'area' => $vData['area'],
                        'address' => $vData['address'],
                        'phone' => $vData['phone'],
                        'email' => $vData['email'],
                        'description' => "Premier hospitality venue hosting royal weddings, banquets, and corporate galas in {$vData['city']}.",
                        'is_active' => true,
                    ]
                );

                foreach ($venueItem['halls'] as $hallItem) {
                    $hallSlug = Str::slug($hallItem['name']);
                    $hall = Hall::firstOrCreate(
                        ['slug' => $hallSlug],
                        [
                            'tenant_id' => $tenant->id,
                            'venue_id' => $venue->id,
                            'name' => $hallItem['name'],
                            'tagline' => $hallItem['tagline'],
                            'min_capacity' => $hallItem['min_capacity'],
                            'max_capacity' => $hallItem['max_capacity'],
                            'base_rent' => $hallItem['base_rent'],
                            'description' => $hallItem['description'],
                            'operating_hours' => 'Morning: 11:30 AM - 3:30 PM | Evening: 7:00 PM - 12:00 AM | Night: 12:30 AM - 4:00 AM',
                            'policies' => "1. 50% advance token deposit required to secure wedding booking.\n2. City banquet regulations apply for music and curfew timings.\n3. Outside catering permitted with prior kitchen clearance.",
                            'cover_image' => $hallItem['cover_image'],
                            'images' => $hallItem['images'],
                            'videos' => ['https://www.youtube.com/embed/7wtfhZwyrcc'],
                            'services' => $defaultServices,
                            'has_air_conditioning' => true,
                            'has_generator_backup' => true,
                            'has_bridal_room' => true,
                            'is_active' => true,
                        ]
                    );

                    // Add catering package
                    Package::firstOrCreate(
                        ['tenant_id' => $tenant->id, 'name' => 'Royal Shahi Banquet Menu — ' . $hallItem['name']],
                        [
                            'description' => 'Mutton Kunna, Chicken Malai Boti, Special Mutton Biryani, Roghani Naan, Salad Bar, Gajar Halwa, and Kulfi Falooda.',
                            'per_head_price' => 3200.00,
                            'min_guests' => 150,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }
}
