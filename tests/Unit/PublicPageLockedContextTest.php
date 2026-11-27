<?php

namespace Tests\Unit;

use App\Livewire\PublicGuestRsvp;
use App\Livewire\PublicHallDetails;
use App\Livewire\PublicVendorProfile;
use Livewire\Attributes\Locked;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PublicPageLockedContextTest extends TestCase
{
    public function test_public_page_models_cannot_be_replaced_by_client_updates(): void
    {
        foreach ([
            PublicHallDetails::class => 'hall',
            PublicVendorProfile::class => 'vendor',
            PublicGuestRsvp::class => 'foundRsvp',
        ] as $componentClass => $propertyName) {
            $property = (new ReflectionClass($componentClass))->getProperty($propertyName);

            $this->assertNotEmpty(
                $property->getAttributes(Locked::class),
                "{$componentClass}::{$propertyName} must be locked."
            );
        }
    }
}
