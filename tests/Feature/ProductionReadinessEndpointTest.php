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

    public function test_production_template_keeps_temporary_uploads_on_the_private_disk(): void
    {
        $template = file_get_contents(base_path('.env.production.example'));

        $this->assertIsString($template);
        $this->assertStringContainsString('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=local', $template);
    }

    public function test_livewire_temporary_upload_limit_matches_the_50_mb_media_contract(): void
    {
        $this->assertSame(
            ['required', 'file', 'max:51200'],
            config('livewire.temporary_file_upload.rules')
        );
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
