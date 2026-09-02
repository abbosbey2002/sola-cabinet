<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Resolve a public device IP to a city/country label for display only.
 *
 * Billing never sends geo fields — only `ip` on /device/list. When lookup is
 * disabled or fails, callers hide the line rather than invent a place name.
 */
final class IpLocation
{
    private const string CACHE_PREFIX = 'geoip:';

    public function __construct(
        private readonly bool $active,
        private readonly int $timeoutSeconds,
        private readonly int $cacheHours,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (bool) config('geoip.active'),
            max(1, (int) config('geoip.timeout', 2)),
            max(1, (int) config('geoip.cache_hours', 24)),
        );
    }

    /**
     * "City, Country" when both are known; otherwise whichever part exists.
     */
    public function formatLocation(?string $ip, ?string $locale = null): ?string
    {
        $resolved = $this->lookup($ip, $locale);

        if ($resolved === null) {
            return null;
        }

        $city = $resolved['city'] ?? null;
        $country = $resolved['country'] ?? null;

        if ($city !== null && $country !== null) {
            return $city.', '.$country;
        }

        return $country ?? $city;
    }

    /**
     * @return array{city: ?string, country: ?string}|null
     */
    public function lookup(?string $ip, ?string $locale = null): ?array
    {
        if (! $this->active || ! is_string($ip) || trim($ip) === '') {
            return null;
        }

        $ip = trim($ip);

        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        $lang = $this->requestLanguage($locale);
        $cacheKey = self::CACHE_PREFIX.md5($ip.':'.$lang);

        return Cache::remember(
            $cacheKey,
            now()->addHours($this->cacheHours),
            fn (): ?array => $this->fetch($ip, $lang),
        );
    }

    /**
     * @return array{city: ?string, country: ?string}|null
     */
    private function fetch(string $ip, string $lang): ?array
    {
        try {
            $response = Http::timeout($this->timeoutSeconds)
                ->acceptJson()
                ->get('https://ipwho.is/'.$ip, ['lang' => $lang]);
        } catch (Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $body = $response->json();

        if (! is_array($body) || ! ($body['success'] ?? false)) {
            return null;
        }

        $city = $this->cleanLabel($body['city'] ?? null);
        $country = $this->cleanLabel($body['country'] ?? null);

        if ($city === null && $country === null) {
            return null;
        }

        return ['city' => $city, 'country' => $country];
    }

    private function requestLanguage(?string $locale): string
    {
        $locale = $locale ?? app()->getLocale();

        return match ($locale) {
            'ru' => 'ru',
            'uz' => 'ru',
            default => 'en',
        };
    }

    private function cleanLabel(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
