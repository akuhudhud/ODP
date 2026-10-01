<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiHealthTest extends TestCase
{
    public function test_api_v1_health_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'API is healthy.',
                'data' => [
                    'service' => 'api',
                    'version' => 'v1',
                ],
            ]);
    }
}
