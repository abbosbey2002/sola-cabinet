<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\Sola\SolaResponse;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * The subscriber's record as the dashboard needs it.
 *
 * Everything the cabinet shows comes from one /abonent/info response, and the
 * spec (docs/task/tz_v1.docx) asks for four fields the old templates never
 * used: the contract number, the next tariff, its cost and the next charge
 * date. Whether billing actually returns them is not documented anywhere, so
 * each one is looked up through a list of candidate keys and reports null when
 * the API stays silent — the templates then render an honest "—" instead of
 * inventing a value.
 *
 * This class is the single place to change once the real key names are known:
 * add the key to the relevant CANDIDATE_* list and the whole dashboard lights
 * up. Nothing else needs touching.
 */
final class AbonentProfile
{
    /** Contract number — "Договор №" in the header. */
    private const CANDIDATE_CONTRACT = ['contract_number', 'contract_num', 'contract_no', 'contract', 'dogovor', 'agreement_number'];

    /** The tariff that takes over at the next billing date. */
    private const CANDIDATE_NEXT_TARIFF = ['next_tariff_name', 'next_tariff', 'future_tariff_name', 'new_tariff_name'];

    /** Cost of that tariff, in the same units as a tariff's "cost" (tiyin). */
    private const CANDIDATE_NEXT_TARIFF_COST = ['next_tariff_cost', 'next_tariff_price', 'future_tariff_cost'];

    /** Cost of the tariff currently in force. */
    private const CANDIDATE_CURRENT_TARIFF_COST = ['curr_tariff_cost', 'tariff_cost', 'curr_tariff_price', 'abon_cost'];

    /** When the subscriber is next charged — "Дата след. списания". */
    private const CANDIDATE_NEXT_CHARGE = ['next_charge_date', 'next_payment_date', 'next_writeoff_date', 'nextpay_date', 'date_next_pay'];

    /** @param array<string, mixed> $body */
    private function __construct(private readonly array $body) {}

    public static function from(SolaResponse $info): self
    {
        return new self($info->body);
    }

    // -- Fields the API has always returned ------------------------------

    public function fullName(): ?string
    {
        return $this->string('name');
    }

    public function email(): ?string
    {
        return $this->string('email');
    }

    public function phone(): ?string
    {
        return $this->string('phone');
    }

    public function address(): ?string
    {
        return $this->string('address');
    }

    public function status(): ?string
    {
        return $this->string('status');
    }

    public function balance(): float
    {
        return (float) ($this->body['saldo'] ?? 0);
    }

    public function currentTariff(): ?string
    {
        return $this->string('curr_tariff_name');
    }

    /**
     * The tariff's id, which is what /tariff/available keys its rows by. Names
     * are not safe to match on: billing pads some of them with a trailing
     * space, so a string compare quietly loses the current tariff.
     */
    public function currentTariffId(): ?string
    {
        return $this->string('curr_tariff_id');
    }

    public function deviceCount(): int
    {
        return (int) ($this->body['device_count'] ?? 0);
    }

    public function activeDeviceCount(): int
    {
        return (int) ($this->body['device_active_count'] ?? 0);
    }

    public function contractDate(): ?CarbonImmutable
    {
        return $this->date('contract_date');
    }

    // -- Fields the spec asks for, pending confirmation from billing -----

    public function contractNumber(): ?string
    {
        return $this->firstString(self::CANDIDATE_CONTRACT);
    }

    public function nextTariff(): ?string
    {
        return $this->firstString(self::CANDIDATE_NEXT_TARIFF);
    }

    /** Cost in soʻm, already divided out of the tiyin the API reports. */
    public function nextTariffCost(): ?float
    {
        return $this->firstCost(self::CANDIDATE_NEXT_TARIFF_COST);
    }

    public function currentTariffCost(): ?float
    {
        return $this->firstCost(self::CANDIDATE_CURRENT_TARIFF_COST);
    }

    public function nextChargeDate(): ?CarbonImmutable
    {
        foreach (self::CANDIDATE_NEXT_CHARGE as $key) {
            if ($date = $this->date($key)) {
                return $date;
            }
        }

        return null;
    }

    /*
     * There is deliberately no fallback HERE, in the profile. Deriving the
     * charge date from contract_date was tried on 2026-08-10 and reverted the
     * same day: the charge does not fall on the contract day at all, it follows
     * the last tariff change.
     *
     * That change date turned up on 2026-08-13 — /tariff/connected carries it
     * as date_begin — and the client stated the rule the same day: the period
     * ends on the day of the month the tariff started. The derivation therefore
     * lives in App\Support\ConnectedTariff::nextChargeDate(), beside the date it
     * is built from, and CabinetController falls back to it.
     *
     * This method stays as the preferred source: if billing ever sends a charge
     * date, it knows things the arithmetic cannot see and should win.
     */

    /**
     * Which of the spec's fields billing did not supply. Rendered nowhere —
     * it exists so the gap can be reported rather than silently absorbed.
     *
     * @return list<string>
     */
    public function missingSpecFields(): array
    {
        return array_keys(array_filter([
            'contract_number' => $this->contractNumber() === null,
            'next_tariff' => $this->nextTariff() === null,
            'next_charge_date' => $this->nextChargeDate() === null,
            'current_tariff_cost' => $this->currentTariffCost() === null,
        ]));
    }

    // -- Readers ---------------------------------------------------------

    private function string(string $key): ?string
    {
        $value = $this->body[$key] ?? null;

        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }

    /** @param list<string> $keys */
    private function firstString(array $keys): ?string
    {
        foreach ($keys as $key) {
            if ($value = $this->string($key)) {
                return $value;
            }
        }

        return null;
    }

    /** @param list<string> $keys */
    private function firstCost(array $keys): ?float
    {
        foreach ($keys as $key) {
            $value = $this->body[$key] ?? null;

            if (is_numeric($value)) {
                return (float) $value / 100;
            }
        }

        return null;
    }

    private function date(string $key): ?CarbonImmutable
    {
        $value = $this->string($key);

        if ($value === null) {
            return null;
        }

        try {
            $date = CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }

        // Billing sends "0000-00-00" for "not set". Carbon does NOT throw on it
        // — it parses to year -1 — so the catch above never sees it and the
        // template would print "30.11.-001" as a contract date.
        return $date->year < 2000 ? null : $date;
    }
}
