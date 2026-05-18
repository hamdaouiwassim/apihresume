<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PricingRegionService
{
    public function resolve(Request $request): array
    {
        $countryCode = $this->resolveCountryCode($request);
        $region = $this->regionForCountryCode($countryCode);
        $config = config("pricing.regions.{$region}");

        return [
            'country_code' => $countryCode,
            'region' => $region,
            'currency' => $config['currency'],
            'free' => $config['free'],
            'pro' => $config['pro'],
        ];
    }

    private function regionForCountryCode(?string $countryCode): string
    {
        $tn = strtoupper(config('pricing.tunisia_country_code', 'TN'));

        if ($countryCode !== null && strtoupper($countryCode) === $tn) {
            return 'tunisia';
        }

        if ($countryCode === null) {
            $default = config('pricing.default_region', 'international');

            return in_array($default, ['tunisia', 'international'], true)
                ? $default
                : 'international';
        }

        return 'international';
    }

    private function resolveCountryCode(Request $request): ?string
    {
        $fromCloudflare = $request->header('CF-IPCountry');
        if ($fromCloudflare && strtoupper($fromCloudflare) !== 'XX') {
            return strtoupper($fromCloudflare);
        }

        $ip = $request->ip();

        if (! $ip || $this->isPrivateOrLocalIp($ip)) {
            return null;
        }

        return $this->lookupCountryFromIp($ip);
    }

    private function isPrivateOrLocalIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    private function lookupCountryFromIp(string $ip): ?string
    {
        try {
            $response = Http::timeout(2)
                ->get("http://ip-api.com/json/{$ip}", [
                    'fields' => 'status,countryCode',
                ]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();
            if (($data['status'] ?? '') !== 'success') {
                return null;
            }

            $code = $data['countryCode'] ?? null;

            return $code ? strtoupper($code) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
