<?php

namespace Database\Seeders;

use App\Models\VendorCategory;
use Illuminate\Database\Seeder;

/**
 * Dynamic vendor categories for the multi-vendor marketplace.
 * Super Admin can manage these from the admin console at runtime.
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
        ];

        foreach ($categories as $category) {
            VendorCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                $category + ['is_active' => true]
            );
        }
    }
}
