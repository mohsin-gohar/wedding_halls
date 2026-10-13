<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\KitchenKot;
use App\Models\MenuItem;

class KitchenOrderService
{
    /**
     * Standard baseline catering consumption weights per guest (in KG or Liters)
     */
    public const MEAT_PER_GUEST_KG = 0.220; // 220g meat per head
    public const RICE_PER_GUEST_KG = 0.160; // 160g basmati rice per head
    public const OIL_PER_GUEST_LITER = 0.050; // 50ml cooking oil per head
    public const YOGURT_PER_GUEST_KG = 0.060; // 60g yogurt for raita
    public const SALAD_PER_GUEST_KG = 0.050; // 50g fresh salad
    public const SWEET_PER_GUEST_KG = 0.080; // 80g sweet/dessert

    /**
     * Automatically generate or refresh a Kitchen Order Ticket (KOT) for a given booking.
     */
    public function generateKotForBooking(Booking $booking, array $customNotes = []): KitchenKot
    {
        $guestCount = (int) ($booking->guest_count > 0 ? $booking->guest_count : 100);

        // Calculate raw material requirements
        $rawMeatKg = round($guestCount * self::MEAT_PER_GUEST_KG, 2);
        $rawRiceKg = round($guestCount * self::RICE_PER_GUEST_KG, 2);
        $rawOilLiters = round($guestCount * self::OIL_PER_GUEST_LITER, 2);

        // Gather dishes summary from booking custom menu or package
        $dishesSummary = [];
        if (!empty($booking->custom_menu_json)) {
            $dishesSummary = $booking->custom_menu_json;
        } elseif ($booking->package && !empty($booking->package->menu_items_json)) {
            $dishesSummary = $booking->package->menu_items_json;
        } else {
            $dishesSummary = [
                'Starters' => ['Mint Raita', 'Fresh Garden Salad', 'Roghni Naan'],
                'Mains' => ['Chicken Biryani Special', 'Mutton Qorma Mughlai'],
                'Dessert' => ['Hot Gulab Jamun', 'Kheer Khaas'],
                'Beverages' => ['Cold Drinks', 'Mineral Water'],
            ];
        }

        // Detailed ingredient breakdown
        $ingredientDetails = [
            'raw_meat' => [
                'name' => 'Mutton / Chicken Cut Pieces',
                'quantity' => $rawMeatKg,
                'unit' => 'KG',
            ],
            'basmati_rice' => [
                'name' => 'Super Basmati Sella Rice',
                'quantity' => $rawRiceKg,
                'unit' => 'KG',
            ],
            'cooking_oil' => [
                'name' => 'Cooking Oil / Banaspati Ghee',
                'quantity' => $rawOilLiters,
                'unit' => 'Liters',
            ],
            'yogurt_raita' => [
                'name' => 'Fresh Dairy Yogurt (Dahi)',
                'quantity' => round($guestCount * self::YOGURT_PER_GUEST_KG, 2),
                'unit' => 'KG',
            ],
            'fresh_salad' => [
                'name' => 'Salad Greens (Cucumber, Tomato, Onion)',
                'quantity' => round($guestCount * self::SALAD_PER_GUEST_KG, 2),
                'unit' => 'KG',
            ],
            'dessert_ration' => [
                'name' => 'Sweet & Dessert Preparation Weight',
                'quantity' => round($guestCount * self::SWEET_PER_GUEST_KG, 2),
                'unit' => 'KG',
            ],
        ];

        // Find or create the KOT
        $kot = KitchenKot::updateOrCreate(
            [
                'booking_id' => $booking->id,
            ],
            [
                'tenant_id' => $booking->tenant_id,
                'kot_number' => KitchenKot::generateKotNumber(),
                'guest_count' => $guestCount,
                'raw_meat_kg' => $rawMeatKg,
                'raw_rice_kg' => $rawRiceKg,
                'raw_oil_liters' => $rawOilLiters,
                'ingredient_details' => $ingredientDetails,
                'dishes_summary' => $dishesSummary,
                'preparation_status' => 'PENDING',
                'custom_instructions' => $customNotes['instructions'] ?? 'Serve piping hot buffet presentation with timely replenishments.',
                'chef_notes' => $customNotes['chef_notes'] ?? null,
            ]
        );

        return $kot;
    }
}
