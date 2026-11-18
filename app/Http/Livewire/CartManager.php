<?php

namespace App\Http\Livewire;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Venue;
use Livewire\Component;

class CartManager extends Component
{
    public Cart $cart;

    public string $checkout_status = '';

    public array $vendors_involved = [];

    public bool $is_checkout = false;

    public string $selected_shift = '';

    public int $guest_count = 0;

    public float $estimated_total = 0.0;

    public function mount(): void
    {
        $this->cart = auth()->user()->cart()
            ->with('items.bookable')
            ->firstOrCreate();
        $this->calculateTotals();
    }

    public function addToCart($bookable, int $quantity = 1): void
    {
        $cart = auth()->user()->cart()->firstOrCreate();

        $existing = $cart->items()
            ->where('bookable_type', get_class($bookable))
            ->where('bookable_id', $bookable->id)
            ->first();

        if ($existing) {
            $existing->quantity += $quantity;
            $existing->save();
        } else {
            $vendorShare = $this->calculateVendorShare($bookable);
            $vendorCommission = 0;

            // Calculate commission based on tenant subscription
            if ($bookable instanceof Venue) {
                $vendorCommission = $bookable->commission_rate / 100 * $bookable->base_rent;
            }

            $cart->items()->create([
                'bookable_type' => get_class($bookable),
                'bookable_id' => $bookable->id,
                'quantity' => $quantity,
                'vendor_share' => $vendorShare,
                'vendor_commission' => $vendorCommission,
            ]);
        }

        $this->calculateTotals();
    }

    private function calculateVendorShare($bookable): float
    {
        // Default vendor share based on type
        $baseShare = match (get_class($bookable)) {
            'App\Models\Venue' => 80.00,
            'App\Models\Caterer' => 70.00,
            'App\Models\Photographer' => 80.00,
            'App\Models\CarFleet' => 85.00,
            'App\Models\Decorator' => 75.00,
            default => 70.00,
        };

        // Adjust based on tenant subscription
        $tenant = auth()->user()?->tenant;
        if ($tenant && $tenant->subscriptionPlan) {
            $commissionRate = $tenant->commissionRate();
            $baseShare = 100 - $commissionRate;
        }

        return $baseShare;
    }

    public function removeFromCart(CartItem $item): void
    {
        $item->delete();
        $this->calculateTotals();
    }

    public function confirmCheckout(): void
    {
        if ($this->cart->items->isEmpty()) {
            $this->checkout_status = 'Cart is empty';

            return;
        }

        // Validate all items are available
        foreach ($this->cart->items as $item) {
            $bookable = $item->bookable;
            if ($bookable && method_exists($bookable, 'is_available')) {
                if (! $bookable->is_available) {
                    $this->checkout_status = 'One or more items are no longer available';

                    return;
                }
            }
        }

        $this->cart->status = 'CONFIRMED';
        $this->cart->save();

        $this->checkout_status = 'confirmed';
        $this->is_checkout = true;

        // Auto-proceed to payment/distribution after a brief delay
        // In a real implementation, this would redirect to payment gateway
    }

    public function calculateTotals(): void
    {
        $subtotal = 0.00;
        $totalCommission = 0.00;

        foreach ($this->cart->items as $item) {
            $bookable = $item->bookable;
            if ($bookable) {
                // Calculate base price based on item type
                $price = match (get_class($bookable)) {
                    'App\Models\Venue' => $bookable->base_rent ?? 0,
                    'App\Models\PhotographerPackage' => $bookable->full_day_rate ?? 0,
                    'App\Models\CarFleet' => $bookable->hourly_rate * 4, // Assume 4 hours
                    'App\Models\MenuItem' => $bookable->per_head_price * $this->guest_count,
                    default => 0,
                };

                $subtotal += $price * $item->quantity;
                $totalCommission += $item->vendor_commission ?? 0;
            }
        }

        // Apply tax (example 15%)
        $taxRate = 0.15;
        $taxAmount = $subtotal * $taxRate;
        $totalAmount = $subtotal + $taxAmount - $totalCommission;

        $this->estimated_total = $totalAmount;
    }

    public function render()
    {
        return view('livewire.cart-manager', [
            'cart' => $this->cart,
        ]);
    }
}
