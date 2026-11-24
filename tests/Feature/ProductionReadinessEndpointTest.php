<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
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

    public function test_readiness_probe_returns_generic_503_when_a_dependency_fails(): void
    {
        DB::shouldReceive('select')
            ->once()
            ->with('SELECT 1')
            ->andThrow(new \RuntimeException('sensitive database connection details'));

        $this->getJson('/ready')
            ->assertStatus(503)
            ->assertExactJson(['status' => 'not_ready'])
            ->assertDontSee('sensitive database connection details');
    }
}
