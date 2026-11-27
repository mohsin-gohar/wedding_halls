<?php

namespace Tests\Unit;

use App\Livewire\HallChat;
use Livewire\Attributes\Locked;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class HallChatLockedContextTest extends TestCase
{
    public function test_guest_identity_and_hall_context_are_locked_livewire_properties(): void
    {
        $reflection = new ReflectionClass(HallChat::class);

        foreach (['hall', 'visitorIp', 'visitorSession'] as $propertyName) {
            $property = $reflection->getProperty($propertyName);
            $this->assertNotEmpty(
                $property->getAttributes(Locked::class),
                "{$propertyName} must not be client-mutable."
            );
        }
    }
}
