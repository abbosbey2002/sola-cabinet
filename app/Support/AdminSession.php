<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cookie;

/**
 * The admin's login state — a signed/encrypted cookie, same mechanism as
 * AbonentSession, kept separate so a subscriber cookie can never be mistaken
 * for an admin one.
 */
final class AdminSession
{
    private const LIFETIME = 480; // 8 hours, in minutes — an admin shift.

    private ?string $pending = null;

    public function login(int $adminId): void
    {
        $this->pending = (string) $adminId;

        Cookie::queue('admin', (string) $adminId, self::LIFETIME);
    }

    public function isAuthenticated(): bool
    {
        return $this->adminId() !== null;
    }

    public function adminId(): ?int
    {
        $value = $this->pending ?? request()->cookie('admin');

        return is_string($value) && $value !== '' ? (int) $value : null;
    }

    public function logout(): void
    {
        $this->pending = null;

        Cookie::queue(Cookie::forget('admin'));
    }
}
