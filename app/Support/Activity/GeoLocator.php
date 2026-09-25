<?php

declare(strict_types=1);

namespace App\Support\Activity;

use Illuminate\Support\Facades\Log;
use MaxMind\Db\Reader;
use Throwable;

/**
 * IP → country and city from a MaxMind GeoLite2-City (or DB-IP Lite) file on
 * this server.
 *
 * Offline by design: the activity journal must not ship subscribers' IPs to a
 * third party (plan §6), which is why this is not App\Support\IpLocation —
 * that one asks ipwho.is and is only for the device list's display label.
 *
 * No file → every lookup is null and the geo columns stay empty. The file is
 * refreshed monthly by ops (docs: DOCKER.md / DEPLOY), not by this app.
 */
final class GeoLocator
{
    private ?Reader $reader = null;

    private bool $opened = false;

    public function __construct(private readonly ?string $databasePath) {}

    /**
     * @return array{country: ?string, city: ?string}
     */
    public function locate(?string $ip): array
    {
        $empty = ['country' => null, 'city' => null];

        if ($ip === null || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return $empty;
        }

        $reader = $this->reader();

        if ($reader === null) {
            return $empty;
        }

        try {
            $record = $reader->get($ip);
        } catch (Throwable) {
            return $empty;
        }

        if (! is_array($record)) {
            return $empty;
        }

        return [
            'country' => self::name($record['country'] ?? null, 64),
            'city' => self::name($record['city'] ?? null, 128),
        ];
    }

    private function reader(): ?Reader
    {
        if ($this->opened) {
            return $this->reader;
        }

        $this->opened = true;

        if ($this->databasePath === null || $this->databasePath === '' || ! is_readable($this->databasePath)) {
            return null;
        }

        try {
            $this->reader = new Reader($this->databasePath);
        } catch (Throwable $e) {
            Log::warning('activity.geoip: database could not be opened', ['reason' => $e->getMessage()]);
        }

        return $this->reader;
    }

    /** English name from a GeoLite2 {names: {en: …}} node. */
    private static function name(mixed $node, int $limit): ?string
    {
        $name = is_array($node) ? ($node['names']['en'] ?? null) : null;

        return is_string($name) && $name !== '' ? mb_substr($name, 0, $limit) : null;
    }
}
