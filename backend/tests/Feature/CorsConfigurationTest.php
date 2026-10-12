<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorsConfigurationTest extends TestCase
{
    public function test_api_allows_the_configured_frontend_origin(): void
    {
        $this->withHeader('Origin', 'http://localhost:5173')
            ->get('/api/me')
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
    }
}
