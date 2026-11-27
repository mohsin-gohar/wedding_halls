<?php

namespace Tests\Unit;

use App\Livewire\HallMediaManager;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class HallMediaUrlValidationTest extends TestCase
{
    public function test_cover_and_gallery_media_urls_reject_active_content_and_css_breakout(): void
    {
        $component = new HallMediaManager;
        $method = new ReflectionMethod(HallMediaManager::class, 'isSafePublicMediaUrl');

        $this->assertTrue($method->invoke($component, 'https://cdn.example.test/hall.jpg'));
        $this->assertTrue($method->invoke($component, '/storage/halls/tenant/hall/image.webp'));
        $this->assertFalse($method->invoke($component, 'javascript:alert(1)'));
        $this->assertFalse($method->invoke($component, 'https://cdn.example.test/x\');background:url(https://evil.test)'));
    }

    public function test_video_urls_are_limited_to_known_embeds_or_direct_video_files(): void
    {
        $component = new HallMediaManager;
        $method = new ReflectionMethod(HallMediaManager::class, 'isSafeVideoUrl');

        $this->assertTrue($method->invoke($component, 'https://www.youtube.com/embed/abcdefghijk?rel=0'));
        $this->assertTrue($method->invoke($component, '/storage/halls/tenant/hall/videos/tour.mp4'));
        $this->assertFalse($method->invoke($component, 'https://attacker.example/phishing'));
        $this->assertFalse($method->invoke($component, 'javascript:alert(1)'));
    }
}
