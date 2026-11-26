<?php

namespace Tests\Unit;

use Tests\TestCase;

class WhatsAppConfigurationTest extends TestCase
{
    public function test_meta_graph_api_default_is_the_supported_version(): void
    {
        $this->assertSame('v26.0', config('services.whatsapp.api_version'));
    }
}
