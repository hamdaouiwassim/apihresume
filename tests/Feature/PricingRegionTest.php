<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PricingRegionTest extends TestCase
{
    use RefreshDatabase;

    public function test_tunisia_pricing_via_cloudflare_header(): void
    {
        $response = $this->withHeaders(['CF-IPCountry' => 'TN'])
            ->getJson('/api/pricing-region');

        $response->assertStatus(200)
            ->assertJsonPath('data.region', 'tunisia')
            ->assertJsonPath('data.pro.formatted', '10 TND')
            ->assertJsonPath('data.free.formatted', '0 TND');
    }

    public function test_international_pricing_for_non_tunisia_country(): void
    {
        $response = $this->withHeaders(['CF-IPCountry' => 'US'])
            ->getJson('/api/pricing-region');

        $response->assertStatus(200)
            ->assertJsonPath('data.region', 'international')
            ->assertJsonPath('data.pro.formatted', '$5')
            ->assertJsonPath('data.free.formatted', '$0');
    }

    public function test_ip_lookup_resolves_tunisia(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response([
                'status' => 'success',
                'countryCode' => 'TN',
            ]),
        ]);

        config(['pricing.default_region' => 'international']);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '41.225.1.1'])
            ->getJson('/api/pricing-region');

        $response->assertStatus(200)
            ->assertJsonPath('data.region', 'tunisia')
            ->assertJsonPath('data.pro.amount', 10);
    }
}
