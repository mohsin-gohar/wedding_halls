<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Services for New Halls
    |--------------------------------------------------------------------------
    |
    | These services are automatically assigned to newly created halls
    | during the super admin provisioning process.
    |
    */

    'default_services' => [
        [
            'title' => 'VIP Bridal Luxury Dressing Suite',
            'price' => 25000,
            'is_complimentary' => true,
            'category' => 'VIP Hospitality',
            'description' => 'Air-conditioned private suite with attached restroom, illuminated Hollywood makeup vanity, and dedicated dressing concierge.',
            'icon' => 'sparkles',
        ],
        [
            'title' => '500 kVA Heavy Duty Standby Generator',
            'price' => 35000,
            'is_complimentary' => true,
            'category' => 'Infrastructure',
            'description' => 'Uninterrupted power system with automatic synchronization and fuel backup covering 100% chiller AC load.',
            'icon' => 'bolt',
        ],
        [
            'title' => 'Bespoke Fresh Rose Stage Decor & Walkway',
            'price' => 85000,
            'is_complimentary' => false,
            'category' => 'Decor & Ambience',
            'description' => 'Fresh imported gladiolus, red roses, golden filigree stage sofa, ambient pin spot lighting, and floral aisle runners.',
            'icon' => 'star',
        ],
        [
            'title' => 'Executive Chauffeur Valet Parking Service',
            'price' => 20000,
            'is_complimentary' => false,
            'category' => 'Guest Logistics',
            'description' => 'Dedicated 8-member uniformed valet team with electronic key logging and paved parking for 250+ cars.',
            'icon' => 'truck',
        ],
        [
            'title' => '4K Drone Aerial Cinematography & LED P3 Wall',
            'price' => 60000,
            'is_complimentary' => false,
            'category' => 'Multimedia & Video',
            'description' => 'Dual 16x10 ft P3 ultra-HD stage display screens with live dual-camera vision mixer for program broadcast.',
            'icon' => 'camera',
        ],
    ],

];
