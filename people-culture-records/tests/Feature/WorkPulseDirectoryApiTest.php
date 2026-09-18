<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkPulseDirectoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_directory_requires_the_integration_token(): void
    {
        config(['services.workpulse.sync_token' => 'test-sync-token']);

        $this->getJson('/api/integrations/workpulse/directory')
            ->assertUnauthorized();
    }

    public function test_directory_returns_a_paginated_safe_payload(): void
    {
        config(['services.workpulse.sync_token' => 'test-sync-token']);

        $this->withToken('test-sync-token')
            ->getJson('/api/integrations/workpulse/directory')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total', 'generated_at'],
            ]);
    }
}
