<?php

namespace Database\Seeders;

use App\Models\VendorCategory;
use Illuminate\Database\Seeder;

/**
 * Dynamic vendor categories for the multi-vendor marketplace.
 * Super Admin can manage these from the admin console at runtime.
 *
 * Structure:
 *  - 14 top-level categories (parent_id = null, sorts 1–14). The original
 *    nine slugs/sorts are kept byte-identical for backward compatibility.
 *  - Subcategories nested under each parent, globally-unique slugs, sorted
 *    inside the parent's band (parentSort*100 + n) so flat dropdowns ordered
 *    by `sort` stay grouped as parent → children.
 *
 * Idempotent: every row is updateOrCreate'd by slug, so re-seeding never
 * duplicates and never resets admin edits to other fields... it does refresh
 * name/tagline/booking_mode/sort to the canonical values below.
 */
class VendorCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Wedding Halls & Marquees', 'slug' => 'wedding-halls', 'tagline' => 'Royal ballrooms, marquees & banquet complexes', 'icon' => 'building-library', 'hero_color' => '#C6A15B', 'booking_mode' => 'SHIFT_VENUE', 'sort' => 1,
                'description' => 'Wedding halls, marriage lawns, marquees, banquet halls, farmhouses, hotels & destination venues with guaranteed single-host shift exclusivity.'],
            ['name' => 'Photography & Videography', 'slug' => 'photography', 'tagline' => 'Cinematic weddings, drone coverage & albums', 'icon' => 'camera', 'hero_color' => '#D29C61', 'booking_mode' => 'HOURLY', 'sort' => 2,
                'description' => 'Wedding photographers, cinematographers, drone coverage, pre-wedding shoots and same-day edits.'],
            ['name' => 'Catering & Food', 'slug' => 'catering', 'tagline' => 'Authentic Mughlai & continental feasts', 'icon' => 'fire', 'hero_color' => '#B4833F', 'booking_mode' => 'PER_HEAD', 'sort' => 3,
                'description' => 'Wedding caterers, live food stations, BBQ, bakeries, cakes and beverage services with per-head menus.'],
            ['name' => 'Decoration & Production', 'slug' => 'decoration', 'tagline' => 'Stages, florals, lighting & LED production', 'icon' => 'sparkles', 'hero_color' => '#8F7239', 'booking_mode' => 'PROJECT', 'sort' => 4,
                'description' => 'Wedding decorators, stage & floral designers, lighting, sound systems and event production.'],
            ['name' => 'Beauty & Bridal', 'slug' => 'beauty-bridal', 'tagline' => 'Bridal makeup, mehndi & styling', 'icon' => 'face-smile', 'hero_color' => '#C6A15B', 'booking_mode' => 'APPOINTMENT', 'sort' => 5,
                'description' => 'Makeup artists, bridal salons, mehndi artists, boutiques and jewellery partners.'],
            ['name' => 'Wedding Planners', 'slug' => 'wedding-planners', 'tagline' => 'Full-service wedding & event management', 'icon' => 'clipboard-document-check', 'hero_color' => '#24211E', 'booking_mode' => 'CONSULTATION', 'sort' => 6,
                'description' => 'Wedding planners, event management companies and destination wedding specialists.'],
            ['name' => 'Transport & Fleet', 'slug' => 'transport', 'tagline' => 'Luxury cars, coasters & valet services', 'icon' => 'truck', 'hero_color' => '#171513', 'booking_mode' => 'TRANSPORT', 'sort' => 7,
                'description' => 'Wedding cars, luxury transport, bus & coaster rentals and valet parking.'],
            ['name' => 'Entertainment', 'slug' => 'entertainment', 'tagline' => 'DJs, live bands, dhol & performers', 'icon' => 'musical-note', 'hero_color' => '#D29C61', 'booking_mode' => 'DATE_EVENT', 'sort' => 8,
                'description' => 'DJs, singers, live bands, dhol players and wedding performers.'],
            ['name' => 'Invitations & Printing', 'slug' => 'invitations-printing', 'tagline' => 'Cards, printing & wedding favours', 'icon' => 'envelope-open', 'hero_color' => '#8F7239', 'booking_mode' => 'PROJECT', 'sort' => 9,
                'description' => 'Invitation designers, printing services and wedding gift suppliers.'],
            ['name' => 'Clothing & Fashion', 'slug' => 'clothing-fashion', 'tagline' => 'Bridal couture, groom wear & tailoring', 'icon' => 'shirt', 'hero_color' => '#B4833F', 'booking_mode' => 'PROJECT', 'sort' => 10,
                'description' => 'Bridal dresses, groom sherwanis, formal wear, footwear, accessories and tailoring ateliers.'],
            ['name' => 'Equipment Rentals', 'slug' => 'equipment-rentals', 'tagline' => 'Tents, seating, power & climate control', 'icon' => 'wrench', 'hero_color' => '#24211E', 'booking_mode' => 'DATE_EVENT', 'sort' => 11,
                'description' => 'Tents & canopies, chairs and tables, generators, cooling, staging and rental logistics for events of any size.'],
            ['name' => 'Cakes & Desserts', 'slug' => 'cakes-desserts', 'tagline' => 'Wedding cakes, mithai & dessert bars', 'icon' => 'cake', 'hero_color' => '#D29C61', 'booking_mode' => 'PROJECT', 'sort' => 12,
                'description' => 'Wedding cake designers, dessert buffets, traditional mithai and bespoke sweet tables.'],
            ['name' => 'Travel & Hospitality', 'slug' => 'travel-hospitality', 'tagline' => 'Guest stays, hotels & honeymoon travel', 'icon' => 'plane', 'hero_color' => '#8F7239', 'booking_mode' => 'DATE_EVENT', 'sort' => 13,
                'description' => 'Guest accommodation, hotel blocks, airport transfers, honeymoon packages and outstation guest logistics.'],
            ['name' => 'Event Services', 'slug' => 'event-services', 'tagline' => 'Security, ushers, cleaning & safety', 'icon' => 'shield-check', 'hero_color' => '#171513', 'booking_mode' => 'DATE_EVENT', 'sort' => 14,
                'description' => 'Event security, ushers & hostesses, post-event cleaning, first-aid teams, parking and crowd management.'],
        ];

        // Subcategories: parent slug => [children]. Child sort = parent sort * 100 + position.
        $subcategories = [
            'wedding-halls' => [
                ['name' => 'Banquet Halls', 'slug' => 'banquet-halls', 'tagline' => 'Indoor banquet complexes'],
                ['name' => 'Marriage Lawns', 'slug' => 'marriage-lawns', 'tagline' => 'Open-air lawns & gardens'],
                ['name' => 'Marquees & Tents', 'slug' => 'marquees-tents', 'tagline' => 'Luxury marquees & custom tents'],
                ['name' => 'Farmhouse Venues', 'slug' => 'farmhouse-venues', 'tagline' => 'Farmhouses & private estates'],
                ['name' => 'Hotel Ballrooms', 'slug' => 'hotel-ballrooms', 'tagline' => 'Hotel & resort ballrooms'],
                ['name' => 'Destination Venues', 'slug' => 'destination-wedding-venues', 'tagline' => 'Hill stations, beaches & destinations'],
            ],
            'photography' => [
                ['name' => 'Wedding Photographers', 'slug' => 'wedding-photographers', 'tagline' => 'Ceremony & portrait coverage'],
                ['name' => 'Wedding Videographers', 'slug' => 'wedding-videographers', 'tagline' => 'Films, teasers & highlight reels'],
                ['name' => 'Drone & Aerial Coverage', 'slug' => 'drone-aerial-coverage', 'tagline' => 'Licensed drone cinematography'],
                ['name' => 'Pre-Wedding Shoots', 'slug' => 'pre-wedding-shoots', 'tagline' => 'Couple & save-the-date shoots'],
                ['name' => 'Same-Day Edit Reels', 'slug' => 'same-day-edit-reels', 'tagline' => 'Same-day wedding edits'],
                ['name' => 'Photo Booths', 'slug' => 'photo-booths', 'tagline' => 'Booths, props & instant prints'],
            ],
            'catering' => [
                ['name' => 'Wedding Caterers', 'slug' => 'wedding-caterers', 'tagline' => 'Full-service wedding catering'],
                ['name' => 'Live Food Stations', 'slug' => 'live-food-stations', 'tagline' => 'Live counters & chef stations'],
                ['name' => 'BBQ & Grills', 'slug' => 'bbq-grill-stations', 'tagline' => 'BBQ, grill & seekh counters'],
                ['name' => 'Beverage Services', 'slug' => 'beverage-services', 'tagline' => 'Drinks, mocktails & beverage bars'],
                ['name' => 'Mughlai Cuisine', 'slug' => 'mughlai-cuisine', 'tagline' => 'Traditional Mughlai menus'],
                ['name' => 'Continental Cuisine', 'slug' => 'continental-cuisine', 'tagline' => 'Continental & fusion menus'],
            ],
            'decoration' => [
                ['name' => 'Stage & Backdrop Design', 'slug' => 'stage-backdrop-design', 'tagline' => 'Stages, mandaps & backdrops'],
                ['name' => 'Floral Designers', 'slug' => 'floral-designers', 'tagline' => 'Fresh & artificial florals'],
                ['name' => 'Lighting Production', 'slug' => 'lighting-production', 'tagline' => 'Architectural & stage lighting'],
                ['name' => 'Sound Systems', 'slug' => 'sound-systems', 'tagline' => 'PA, DJ systems & line arrays'],
                ['name' => 'LED & Video Walls', 'slug' => 'led-video-walls', 'tagline' => 'LED screens & projection'],
                ['name' => 'Theme & Props Styling', 'slug' => 'theme-props-styling', 'tagline' => 'Themes, props & set styling'],
            ],
            'beauty-bridal' => [
                ['name' => 'Bridal Makeup Artists', 'slug' => 'bridal-makeup-artists', 'tagline' => 'HD & airbrush bridal makeup'],
                ['name' => 'Mehndi Artists', 'slug' => 'mehndi-artists', 'tagline' => 'Bridal & guest mehndi'],
                ['name' => 'Hair Stylists', 'slug' => 'hair-styling', 'tagline' => 'Bridal hair & styling'],
                ['name' => 'Bridal Salons', 'slug' => 'bridal-salons', 'tagline' => 'Full-service bridal salons'],
                ['name' => 'Wedding Jewellery', 'slug' => 'wedding-jewellery', 'tagline' => 'Fine & fashion jewellery'],
                ['name' => 'Nail Art & Beauty', 'slug' => 'nail-art-beauty', 'tagline' => 'Nail art & beauty treatments'],
            ],
            'wedding-planners' => [
                ['name' => 'Full-Service Planners', 'slug' => 'full-service-planners', 'tagline' => 'End-to-end wedding planning'],
                ['name' => 'Destination Wedding Planners', 'slug' => 'destination-wedding-planners', 'tagline' => 'Abroad & hill-station weddings'],
                ['name' => 'Event Management Companies', 'slug' => 'event-management-companies', 'tagline' => 'Corporate & large events'],
                ['name' => 'Wedding Day Coordinators', 'slug' => 'wedding-day-coordinators', 'tagline' => 'On-day coordination'],
                ['name' => 'Theme & Concept Designers', 'slug' => 'theme-concept-designers', 'tagline' => 'Concepts, themes & moodboards'],
            ],
            'transport' => [
                ['name' => 'Wedding Cars', 'slug' => 'wedding-cars', 'tagline' => 'Decorated wedding cars'],
                ['name' => 'Luxury Sedans & SUVs', 'slug' => 'luxury-sedans-suvs', 'tagline' => 'Executive sedans & SUVs'],
                ['name' => 'Coasters & Buses', 'slug' => 'coasters-buses', 'tagline' => 'Guest coasters & buses'],
                ['name' => 'Valet Parking', 'slug' => 'valet-parking', 'tagline' => 'Valet & parking attendants'],
                ['name' => 'Classic & Vintage Cars', 'slug' => 'classic-vintage-cars', 'tagline' => 'Classic & vintage fleets'],
                ['name' => 'Guest Shuttle Services', 'slug' => 'guest-shuttle-services', 'tagline' => 'Hotel & venue shuttles'],
            ],
            'entertainment' => [
                ['name' => 'DJs', 'slug' => 'djs', 'tagline' => 'Wedding & sangeet DJs'],
                ['name' => 'Live Bands', 'slug' => 'live-bands', 'tagline' => 'Live bands & ensembles'],
                ['name' => 'Qawwali & Singers', 'slug' => 'qawwali-singers', 'tagline' => 'Qawwali, nazm & singers'],
                ['name' => 'Dhol & Dholki', 'slug' => 'dhol-dholki', 'tagline' => 'Dhol players & dholki nights'],
                ['name' => 'Folk Performers', 'slug' => 'folk-performers', 'tagline' => 'Folk & regional performers'],
                ['name' => 'Stage Performers', 'slug' => 'stage-performers', 'tagline' => 'Dancers, acts & stage shows'],
            ],
            'invitations-printing' => [
                ['name' => 'Invitation Designers', 'slug' => 'invitation-designers', 'tagline' => 'Bespoke invitation design'],
                ['name' => 'Wedding Stationery Printing', 'slug' => 'wedding-stationery-printing', 'tagline' => 'Cards, menus & place cards'],
                ['name' => 'Calligraphy & Signage', 'slug' => 'calligraphy-signage', 'tagline' => 'Hand lettering & signage'],
                ['name' => 'Wedding Favours', 'slug' => 'wedding-favours', 'tagline' => 'Return gifts & favours'],
                ['name' => 'Welcome Boards', 'slug' => 'welcome-boards', 'tagline' => 'Welcome & seating boards'],
                ['name' => 'Gift Packaging', 'slug' => 'gift-packaging', 'tagline' => 'Boxes, wrapping & ribbons'],
            ],
            'clothing-fashion' => [
                ['name' => 'Bridal Dresses', 'slug' => 'bridal-dresses', 'tagline' => 'Lehengas, gowns & bridal couture'],
                ['name' => 'Groom Sherwanis', 'slug' => 'groom-sherwanis', 'tagline' => 'Sherwanis & groom wear'],
                ['name' => 'Suits & Tuxedos', 'slug' => 'suits-tuxedos', 'tagline' => 'Formal suits & tuxedos'],
                ['name' => 'Bridesmaid Dresses', 'slug' => 'bridesmaid-dresses', 'tagline' => 'Bridesmaid & family outfits'],
                ['name' => 'Footwear & Accessories', 'slug' => 'footwear-accessories', 'tagline' => 'Shoes, clutches & accessories'],
                ['name' => 'Tailoring & Alterations', 'slug' => 'tailoring-alterations', 'tagline' => 'Bespoke tailoring & fittings'],
            ],
            'equipment-rentals' => [
                ['name' => 'Tents & Canopies', 'slug' => 'tents-canopies', 'tagline' => 'German tents, canopies & shamianas'],
                ['name' => 'Chairs & Tables', 'slug' => 'chairs-tables-rental', 'tagline' => 'Seating, tables & lounges'],
                ['name' => 'Generators & Power', 'slug' => 'generators-power', 'tagline' => 'Generators & power backup'],
                ['name' => 'Climate Control', 'slug' => 'climate-control-rental', 'tagline' => 'AC, heating & misting fans'],
                ['name' => 'Portable Stages', 'slug' => 'portable-stages', 'tagline' => 'Stages, podiums & risers'],
                ['name' => 'Linen & Tablecloths', 'slug' => 'linen-tablecloths', 'tagline' => 'Linen, runners & drapes'],
            ],
            'cakes-desserts' => [
                ['name' => 'Wedding Cakes', 'slug' => 'wedding-cakes', 'tagline' => 'Tiered & custom wedding cakes'],
                ['name' => 'Dessert Buffets', 'slug' => 'dessert-buffets', 'tagline' => 'Dessert bars & buffets'],
                ['name' => 'Traditional Mithai', 'slug' => 'traditional-mithai', 'tagline' => 'Mithai boxes & sweet tables'],
                ['name' => 'Custom Cookies & Favors', 'slug' => 'custom-cookies-favors', 'tagline' => 'Decorated cookies & sweet favors'],
                ['name' => 'Dietary Desserts', 'slug' => 'dietary-desserts', 'tagline' => 'Sugar-free & specialty desserts'],
                ['name' => 'Cake Toppers & Decor', 'slug' => 'cake-toppers-decor', 'tagline' => 'Toppers, stands & cake decor'],
            ],
            'travel-hospitality' => [
                ['name' => 'Guest Accommodation', 'slug' => 'guest-accommodation', 'tagline' => 'Rooms & guest houses'],
                ['name' => 'Hotel Block Bookings', 'slug' => 'hotel-block-bookings', 'tagline' => 'Bulk hotel room blocks'],
                ['name' => 'Airport Pickup Service', 'slug' => 'airport-pickup-service', 'tagline' => 'Airport transfers for guests'],
                ['name' => 'Honeymoon Packages', 'slug' => 'honeymoon-packages', 'tagline' => 'Honeymoon & travel packages'],
                ['name' => 'Guest Welcome Kits', 'slug' => 'guest-welcome-kits', 'tagline' => 'Welcome bags & itineraries'],
                ['name' => 'City Tours for Guests', 'slug' => 'city-tours-guests', 'tagline' => 'Guided tours for outstation guests'],
            ],
            'event-services' => [
                ['name' => 'Event Security', 'slug' => 'event-security', 'tagline' => 'Guards & access control'],
                ['name' => 'Ushers & Hostesses', 'slug' => 'ushers-hostesses', 'tagline' => 'Guest welcome & seating'],
                ['name' => 'Post-Event Cleaning', 'slug' => 'post-event-cleaning', 'tagline' => 'Setup & teardown cleaning'],
                ['name' => 'First-Aid Teams', 'slug' => 'first-aid-teams', 'tagline' => 'On-site medics & first aid'],
                ['name' => 'Parking & Traffic Management', 'slug' => 'parking-traffic-management', 'tagline' => 'Parking marshals & traffic'],
                ['name' => 'Waste Management', 'slug' => 'waste-management', 'tagline' => 'Segregation & disposal'],
            ],
        ];

        foreach ($categories as $category) {
            VendorCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                $category + ['is_active' => true, 'parent_id' => null]
            );
        }

        $parents = VendorCategory::query()
            ->whereIn('slug', array_keys($subcategories))
            ->pluck('id', 'slug');

        foreach ($subcategories as $parentSlug => $children) {
            if (! $parents->has($parentSlug)) {
                // Parent insert above failed or slug was renamed — never
                // create orphans with a null parent (they would surface as
                // top-level tabs in the marketplace).
                $this->command?->error("VendorCategorySeeder: parent '{$parentSlug}' missing, skipping its subcategories.");

                continue;
            }

            $parentSort = (int) VendorCategory::query()->where('slug', $parentSlug)->value('sort');
            $parentIcon = (string) VendorCategory::query()->where('slug', $parentSlug)->value('icon');

            foreach ($children as $position => $child) {
                VendorCategory::query()->updateOrCreate(
                    ['slug' => $child['slug']],
                    [
                        'parent_id' => $parents[$parentSlug],
                        'name' => $child['name'],
                        'tagline' => $child['tagline'],
                        'icon' => $parentIcon,
                        'hero_color' => $this->parentColor($parentSlug),
                        'booking_mode' => $this->parentBookingMode($parentSlug),
                        'description' => $child['tagline'],
                        'is_active' => true,
                        'sort' => ($parentSort * 100) + $position + 1,
                    ]
                );
            }
        }
    }

    private function parentColor(string $parentSlug): string
    {
        return match ($parentSlug) {
            'wedding-halls', 'beauty-bridal' => '#C6A15B',
            'photography', 'entertainment', 'cakes-desserts' => '#D29C61',
            'catering' => '#B4833F',
            'decoration', 'invitations-printing', 'travel-hospitality' => '#8F7239',
            'wedding-planners', 'equipment-rentals' => '#24211E',
            default => '#171513',
        };
    }

    private function parentBookingMode(string $parentSlug): string
    {
        return match ($parentSlug) {
            'wedding-halls' => 'SHIFT_VENUE',
            'photography' => 'HOURLY',
            'catering' => 'PER_HEAD',
            'decoration', 'invitations-printing', 'clothing-fashion', 'cakes-desserts' => 'PROJECT',
            'beauty-bridal' => 'APPOINTMENT',
            'wedding-planners' => 'CONSULTATION',
            'transport' => 'TRANSPORT',
            default => 'DATE_EVENT',
        };
    }
}
