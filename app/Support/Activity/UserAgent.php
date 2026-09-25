<?php

declare(strict_types=1);

namespace App\Support\Activity;

/**
 * Browser, OS and device class out of a User-Agent header, for the statistics
 * segments ("which browsers do our subscribers use").
 *
 * Deliberately small instead of a UA-database dependency: the segments need a
 * handful of families, not every bot and smart TV. A string this does not
 * recognise is stored raw in activity_events.user_agent anyway, so nothing is
 * lost and a better parser can re-derive the columns later.
 */
final readonly class UserAgent
{
    /** Order matters: Edge and Opera also say "Chrome", Chrome also says "Safari". */
    private const BROWSERS = [
        'Edge' => '/Edg(?:e|A|iOS)?\/([\d.]+)/',
        'Opera' => '/(?:OPR|Opera)\/([\d.]+)/',
        'Yandex' => '/YaBrowser\/([\d.]+)/',
        'Samsung' => '/SamsungBrowser\/([\d.]+)/',
        'Firefox' => '/(?:Firefox|FxiOS)\/([\d.]+)/',
        'Chrome' => '/(?:Chrome|CriOS)\/([\d.]+)/',
        'Safari' => '/Version\/([\d.]+).*Safari\//',
    ];

    public function __construct(
        public ?string $browser,
        public ?string $browserVersion,
        public ?string $os,
        public ?string $osVersion,
        public ?string $deviceType,
        public ?string $deviceModel,
    ) {}

    public static function parse(?string $header): self
    {
        $ua = (string) $header;

        if ($ua === '') {
            return new self(null, null, null, null, null, null);
        }

        [$browser, $browserVersion] = self::browser($ua);
        [$os, $osVersion] = self::os($ua);

        return new self($browser, $browserVersion, $os, $osVersion, self::deviceType($ua), self::deviceModel($ua));
    }

    /** @return array{0: ?string, 1: ?string} */
    private static function browser(string $ua): array
    {
        foreach (self::BROWSERS as $name => $pattern) {
            if (preg_match($pattern, $ua, $match) === 1) {
                return [$name, self::majorMinor($match[1])];
            }
        }

        return [null, null];
    }

    /** @return array{0: ?string, 1: ?string} */
    private static function os(string $ua): array
    {
        return match (true) {
            preg_match('/Windows NT ([\d.]+)/', $ua, $m) === 1 => ['Windows', $m[1]],
            preg_match('/Android ([\d.]+)/', $ua, $m) === 1 => ['Android', self::majorMinor($m[1])],
            preg_match('/(?:iPhone|CPU) OS (\d+)[_.](\d+)/', $ua, $m) === 1 => ['iOS', "{$m[1]}.{$m[2]}"],
            preg_match('/Mac OS X (\d+)[_.](\d+)/', $ua, $m) === 1 => ['macOS', "{$m[1]}.{$m[2]}"],
            str_contains($ua, 'CrOS') => ['ChromeOS', null],
            str_contains($ua, 'Linux') => ['Linux', null],
            default => [null, null],
        };
    }

    private static function deviceType(string $ua): string
    {
        return match (true) {
            preg_match('/bot|crawl|spider|slurp/i', $ua) === 1 => 'bot',
            str_contains($ua, 'iPad'), str_contains($ua, 'Tablet') => 'tablet',
            str_contains($ua, 'Android') && ! str_contains($ua, 'Mobile') => 'tablet',
            str_contains($ua, 'Mobi'), str_contains($ua, 'iPhone'), str_contains($ua, 'Android') => 'mobile',
            default => 'desktop',
        };
    }

    private static function deviceModel(string $ua): ?string
    {
        if (str_contains($ua, 'iPhone')) {
            return 'iPhone';
        }

        if (str_contains($ua, 'iPad')) {
            return 'iPad';
        }

        // "Linux; Android 13; SM-A515F Build/TP1A…)" or "…; Android 10; K)".
        if (preg_match('/Android [\d.]+; ([^;)]+?)(?: Build\/[^;)]*)?\)/', $ua, $match) === 1) {
            $model = trim($match[1]);

            // Chrome's reduced UA replaces the model with a bare "K".
            return $model === '' || $model === 'K' ? null : mb_substr($model, 0, 64);
        }

        return null;
    }

    private static function majorMinor(string $version): string
    {
        $parts = explode('.', $version);

        return mb_substr(implode('.', array_slice($parts, 0, 2)), 0, 32);
    }
}
