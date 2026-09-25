<?php

declare(strict_types=1);

namespace App\Support\Activity;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Everything about *where a request came from* that the journal stores next to
 * an event: network, device, screen and request metadata. One place builds it
 * so activity_events and tariff_changes describe a request the same way.
 *
 * Never collected: cookie values, the SMS code, tokens, billing responses.
 */
final class RequestContext
{
    /**
     * Written by resources/js/modules/activity.js: "screenW,screenH,viewportW,theme,textSize".
     * Plain (unencrypted, see bootstrap/app.php) and entirely client-controlled,
     * so every part is re-validated here.
     */
    public const SCREEN_COOKIE = 'sola_scr';

    private const THEMES = ['light', 'dark', 'system'];

    private const TEXT_SIZES = ['normal', 'lg', 'xl'];

    public function __construct(private readonly GeoLocator $geo) {}

    /**
     * The request's own id — the incoming X-Request-Id when the gateway sent a
     * sane one, otherwise a fresh UUID. Stable for the whole request.
     */
    public static function requestId(Request $request): string
    {
        $existing = $request->attributes->get('activity.request_id');

        if (is_string($existing)) {
            return $existing;
        }

        $header = (string) $request->headers->get('X-Request-Id', '');
        $id = preg_match('/^[A-Za-z0-9._-]{8,64}$/', $header) === 1 ? $header : (string) Str::uuid();

        $request->attributes->set('activity.request_id', $id);

        return $id;
    }

    /**
     * Columns shared by activity_events and tariff_changes.
     *
     * @return array<string, string|null>
     */
    public function basics(Request $request): array
    {
        $agent = UserAgent::parse($request->userAgent());

        return [
            'ip' => $request->ip(),
            'user_agent' => self::limit($request->userAgent(), 512),
            'device_type' => $agent->deviceType,
            'locale' => self::limit(app()->getLocale(), 5),
            'session_id' => self::sessionFingerprint($request),
            'request_id' => self::requestId($request),
        ];
    }

    /**
     * The full set for an activity_events row.
     *
     * @return array<string, int|string|null>
     */
    public function forEvent(Request $request): array
    {
        $agent = UserAgent::parse($request->userAgent());
        $ip = $request->ip();
        $geo = $this->geo->locate($ip);

        return $this->basics($request) + [
            'ip_forwarded' => self::limit($request->headers->get('X-Forwarded-For'), 255),
            'geo_country' => $geo['country'],
            'geo_city' => $geo['city'],
            'browser' => $agent->browser,
            'browser_version' => $agent->browserVersion,
            'os' => $agent->os,
            'os_version' => $agent->osVersion,
            'device_model' => $agent->deviceModel,
            'route' => self::limit($request->route()?->getName(), 64),
            'method' => $request->method(),
            'path' => self::limit('/'.ltrim($request->path(), '/'), 255),
            'referrer' => self::limit(self::withoutQuery($request->headers->get('referer')), 512),
        ] + self::screen($request);
    }

    /**
     * @return array{screen_w: ?int, screen_h: ?int, viewport_w: ?int, theme: ?string, text_size: ?string}
     */
    public static function screen(Request $request): array
    {
        $parts = explode(',', (string) $request->cookie(self::SCREEN_COOKIE, ''));

        return [
            'screen_w' => self::dimension($parts[0] ?? null),
            'screen_h' => self::dimension($parts[1] ?? null),
            'viewport_w' => self::dimension($parts[2] ?? null),
            'theme' => in_array($parts[3] ?? null, self::THEMES, true) ? $parts[3] : null,
            'text_size' => in_array($parts[4] ?? null, self::TEXT_SIZES, true) ? $parts[4] : null,
        ];
    }

    /**
     * A one-way fingerprint of the Laravel session id: enough to group one
     * visit's events together, useless to anyone reading the table who would
     * like to ride that session.
     */
    private static function sessionFingerprint(Request $request): ?string
    {
        if (! $request->hasSession()) {
            return null;
        }

        $id = $request->session()->getId();

        return $id === '' ? null : substr(hash('sha256', $id), 0, 32);
    }

    private static function dimension(?string $value): ?int
    {
        if ($value === null || preg_match('/^\d{1,5}$/', $value) !== 1) {
            return null;
        }

        $pixels = (int) $value;

        return $pixels > 0 && $pixels <= 20000 ? $pixels : null;
    }

    /** A referrer's query string can carry anything; the path is enough. */
    private static function withoutQuery(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        return strtok($url, '?#') ?: null;
    }

    private static function limit(?string $value, int $length): ?string
    {
        return $value === null || $value === '' ? null : mb_substr($value, 0, $length);
    }
}
