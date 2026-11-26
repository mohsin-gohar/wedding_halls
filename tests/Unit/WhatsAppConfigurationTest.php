<?php

namespace Tests\Unit;

use Tests\TestCase;

class WhatsAppConfigurationTest extends TestCase
{
    public function test_meta_graph_api_default_is_the_supported_version(): void
    {
        // The fallback lives in config/services.php; the local .env may
        // legitimately pin a different supported version, so assert both the
        // documented fallback and that runtime config honours the override.
        $source = (string) file_get_contents(config_path('services.php'));
        $this->assertStringContainsString(
            "'api_version' => env('WHATSAPP_API_VERSION', 'v26.0')",
            $source
        );
        $this->assertSame(
            env('WHATSAPP_API_VERSION', 'v26.0'),
            config('services.whatsapp.api_version')
        );
    }
}
