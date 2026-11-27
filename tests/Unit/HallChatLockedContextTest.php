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
        foreach ([HallChat::class, VenueChatWidget::class] as $componentClass) {
            $reflection = new ReflectionClass($componentClass);

            foreach (['hall', 'visitorIp', 'visitorSession'] as $propertyName) {
                $property = $reflection->getProperty($propertyName);
                $this->assertNotEmpty(
                    $property->getAttributes(Locked::class),
                    "{$componentClass}::{$propertyName} must not be client-mutable."
                );
            }
        }
    }
}
