<?php

namespace Tests\Unit;

use App\Support\Media;
use Tests\TestCase;

class MediaTest extends TestCase
{
    public function test_external_media_urls_are_preserved(): void
    {
        $url = 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?q=80&w=1200';

        $this->assertSame($url, Media::publicUrl($url));
    }

    public function test_storage_paths_keep_the_storage_prefix(): void
    {
        $this->assertSame(asset('storage/vendor/test.jpg'), Media::publicUrl('storage/vendor/test.jpg'));
    }
}
