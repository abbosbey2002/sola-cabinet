<?php

declare(strict_types=1);

namespace App\Support\Admin;

/**
 * Subscribers' phone and name as a given admin is allowed to see them — in
 * full, or masked for a role without AdminAbility::ViewPersonalData (plan §7:
 * the analyst sees "+998 90 *** ** 67").
 *
 * Views and the CSV export both go through this, so the rule cannot drift
 * between the screen and the file.
 */
final readonly class PersonalData
{
    public function __construct(private bool $unmasked) {}

    public static function for(CurrentAdmin $admin): self
    {
        return new self($admin->can(AdminAbility::ViewPersonalData));
    }

    public function isUnmasked(): bool
    {
        return $this->unmasked;
    }

    public function phone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';

        // 998 90 123 45 67 — the country code and operator stay, the rest is
        // shown only to a role that may see it.
        if (strlen($digits) === 12 && str_starts_with($digits, '998')) {
            return $this->unmasked
                ? sprintf('+998 %s %s %s %s', substr($digits, 3, 2), substr($digits, 5, 3), substr($digits, 8, 2), substr($digits, 10, 2))
                : sprintf('+998 %s *** ** %s', substr($digits, 3, 2), substr($digits, 10, 2));
        }

        return $this->unmasked ? $phone : str_repeat('*', max(0, strlen($digits) - 2)).substr($digits, -2);
    }

    /**
     * The billing login (contract number). Billing usually sets it to the
     * subscriber's phone number, so one that looks like a phone is masked the
     * same way; any other contract number is not personal data and stays.
     */
    public function login(?string $login): ?string
    {
        if ($login !== null && preg_match('/^\+?998\d{9}$/', $login) === 1) {
            return $this->phone($login);
        }

        return $login === '' ? null : $login;
    }

    public function name(?string $name): ?string
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        if ($this->unmasked) {
            return $name;
        }

        // Every word keeps its first letter: "Tester Testov" → "T***** T*****".
        return implode(' ', array_map(
            fn (string $word): string => mb_substr($word, 0, 1).str_repeat('*', max(0, mb_strlen($word) - 1)),
            preg_split('/\s+/u', trim($name)) ?: [],
        ));
    }
}
