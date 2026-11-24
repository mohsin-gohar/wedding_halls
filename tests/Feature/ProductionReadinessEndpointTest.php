<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionReadinessEndpointTest extends TestCase
{
    public function test_readiness_probe_checks_dependencies_and_returns_no_internal_details(): void
    {
        Storage::fake('local');

        $this->getJson('/ready')
            ->assertOk()
            ->assertExactJson(['status' => 'ready']);
    }
}
