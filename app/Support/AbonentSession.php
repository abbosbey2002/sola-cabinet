<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cookie;

/**
 * The subscriber's state for the current browser session.
 *
 * There is no database and no Laravel guard: the cabinet keeps its state in
 * encrypted cookies, which Laravel signs, so the values cannot be forged. The
 * cookie names are part of the deployed contract — renaming one logs every
 * live visitor out.
 *
 * Queued cookies are only visible to the *next* request, so writes are also
 * kept in memory and preferred on read. That makes "set then read" correct
 * inside a single request.
 */
final class AbonentSession
{
    private const LIFETIME = 1000;

    private const LOCALE_LIFETIME = 10000;

    /** Subscriber type: temporary. */
    public const TYPE_TEMPORARY = 0;

    /** Subscriber type: one-off. */
    public const TYPE_ONE_TIME = 1;

    /**
     * Subscriber type: permanent — the lowest code allowed to manage devices.
     *
     * Billing splits the permanent subscriber into a legal entity and an
     * individual, so this is a floor and not a value to compare against. See
     * isPermanent().
     */
    public const TYPE_PERMANENT = 2;

    /** @var array<string, string> Values written during this request. */
    private array $pending = [];

    public function markVerified(): void
    {
        $this->put('verify', '1');
    }

    public function isVerified(): bool
    {
        return (bool) $this->read('verify');
    }

    public function setLogin(string $login): void
    {
        $this->put('login', $login);
    }

    public function login(): string
    {
        return (string) $this->read('login');
    }

    /**
     * Billing's own login for this account — /identify's per-account `login`
     * field, a distinct billing-assigned value that just usually happens to
     * equal the phone number. Not to be confused with login()/setLogin()
     * above, which holds the phone the subscriber typed to sign in and is
     * what OTP verification is checked against.
     */
    public function setBillingLogin(string $login): void
    {
        $this->put('billing_login', $login);
    }

    public function billingLogin(): string
    {
        return (string) $this->read('billing_login');
    }

    /**
     * Stored as an integer because the API is fed the raw numeric phone when
     * listing accounts; changing the type would change the signed JSON body.
     */
    public function setPhone(int $phone): void
    {
        $this->put('phone', (string) $phone);
    }

    public function phone(): int
    {
        return (int) $this->read('phone');
    }

    public function setAccountId(string $accountId): void
    {
        $this->put('account', $accountId);
    }

    public function accountId(): string
    {
        return (string) $this->read('account');
    }

    public function setFullName(string $fullName): void
    {
        $this->put('full_name', $fullName);
    }

    public function fullName(): string
    {
        return (string) $this->read('full_name');
    }

    public function setAbonentType(int $type): void
    {
        $this->put('data', (string) json_encode(['type' => $type]));
    }

    public function abonentType(): ?int
    {
        $decoded = json_decode((string) $this->read('data'), true);

        return is_array($decoded) && isset($decoded['type']) && is_numeric($decoded['type'])
            ? (int) $decoded['type']
            : null;
    }

    /**
     * A subscriber with the full run of the cabinet — devices and tariff
     * switching, the two things that spend money.
     *
     * The test is "not one of the restricted kinds", not "equals 2". Billing
     * does not report a single permanent type: it separates a legal entity from
     * an individual, and the codes above 1 are that split. Comparing to 2
     * exactly locked one of those groups out of their own account while the
     * badge beside their name still read "Doimiy" — the old templates got this
     * right with a trailing @else, and x-account-type still does.
     *
     * The two restricted kinds are billing's own: it answers 121 "Временным не
     * доступно" and 122 "Временным и разовым не доступно" for them. This gate
     * is a courtesy that keeps a subscriber from being offered something they
     * cannot have; the API remains the authority and every controller checks
     * again before spending anything.
     *
     * A session with no type at all is not a subscriber kind — it is a session
     * from before this cookie existed, and it fails closed.
     */
    public function isPermanent(): bool
    {
        $type = $this->abonentType();

        return $type !== null && $type >= self::TYPE_PERMANENT;
    }

    public function setLocale(string $locale): void
    {
        $this->pending['lang'] = $locale;

        Cookie::queue('lang', $locale, self::LOCALE_LIFETIME);
    }

    public function locale(): ?string
    {
        $locale = $this->read('lang');

        return $locale === null || $locale === '' ? null : (string) $locale;
    }

    /**
     * Drop everything that identifies the subscriber.
     */
    public function flush(): void
    {
        foreach (['account', 'full_name', 'phone', 'login', 'billing_login', 'verify', 'data'] as $name) {
            unset($this->pending[$name]);

            Cookie::queue(Cookie::forget($name));
        }
    }

    private function put(string $name, string $value): void
    {
        $this->pending[$name] = $value;

        Cookie::queue($name, $value, self::LIFETIME);
    }

    private function read(string $name): ?string
    {
        if (array_key_exists($name, $this->pending)) {
            return $this->pending[$name];
        }

        $value = request()->cookie($name);

        return is_string($value) ? $value : null;
    }
}
