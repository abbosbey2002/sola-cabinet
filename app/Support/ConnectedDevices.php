<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * Display helpers for /device/list rows — connect date, IP, geo label.
 */
final class ConnectedDevices
{
    /**
     * @param  list<array<string, mixed>>  $devices
     */
    public static function metricHint(array $devices, IpLocation $geo): ?string
    {
        $latest = self::latest($devices);

        if ($latest === null) {
            return null;
        }

        $parts = [];

        if ($latest['connect_date'] instanceof CarbonImmutable) {
            $parts[] = __('app.dash.devices_connected_on', [
                'date' => $latest['connect_date']->format('d.m.Y'),
            ]);
        }

        $location = $geo->formatLocation($latest['ip']);

        if ($location !== null) {
            $parts[] = $location;
        }

        return $parts !== [] ? implode(' · ', $parts) : null;
    }

    /**
     * @param  list<array<string, mixed>>  $devices
     * @return list<array<string, mixed>>
     */
    public static function withLocations(array $devices, IpLocation $geo): array
    {
        return array_map(function (array $device) use ($geo): array {
            $ip = is_string($device['ip'] ?? null) ? trim($device['ip']) : '';
            $device['location'] = $ip !== '' ? $geo->formatLocation($ip) : null;

            return $device;
        }, $devices);
    }

    /**
     * The device with the most recent connect_date — the one the dashboard
     * summary line describes.
     *
     * @param  list<array<string, mixed>>  $devices
     * @return array{connect_date: ?CarbonImmutable, ip: ?string}|null
     */
    public static function latest(array $devices): ?array
    {
        $latestDate = null;
        $latestIp = null;

        foreach ($devices as $device) {
            $raw = $device['connect_date'] ?? null;

            if (! is_string($raw) || $raw === '') {
                continue;
            }

            try {
                $parsed = CarbonImmutable::parse($raw);
            } catch (Throwable) {
                continue;
            }

            if ($latestDate === null || $parsed->greaterThan($latestDate)) {
                $latestDate = $parsed;
                $ip = is_string($device['ip'] ?? null) ? trim($device['ip']) : '';

                $latestIp = $ip !== '' ? $ip : null;
            }
        }

        if ($latestDate === null && $latestIp === null) {
            return null;
        }

        return [
            'connect_date' => $latestDate,
            'ip' => $latestIp,
        ];
    }
}
