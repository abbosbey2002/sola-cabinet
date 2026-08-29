<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\Sola\SolaResponse;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
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

    /**
     * When the subscriber is next charged — "Дата след. списания".
     *
     * None of these have ever been observed in a live response; they were
     * guesses made before `charge_date` (below) was confirmed as the real
     * field. Checked first only because they were here first and cost
     * nothing to check — not because they should outrank `charge_date`. If
     * one of these ever turns up alongside `charge_date` on a live account,
     * that is worth investigating rather than trusting silently: `charge_date`
     * is the confirmed source now.
     */
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

    /**
     * True when the subscriber is a legal entity (yuridik shaxs) rather than
     * an individual (jismoniy shaxs), per the API's `legal` field.
     *
     * Confirmed by the client on 2026-08-18: an individual reports either the
     * literal string "Физическое лицо" or 0; any other value — most notably
     * "Юридическое лицо" — is a legal entity. The tariff section is not
     * offered to a legal entity at all, not just the switch action: see
     * TariffController and partials/topbar.blade.php.
     *
     * The field is missing on accounts billing has not migrated yet — absent,
     * nothing is restricted, the same as every account behaved before this
     * field existed.
     */
    public function isLegalEntity(): bool
    {
        $value = $this->body['legal'] ?? null;

        if ($value === null) {
            return false;
        }

        if (is_string($value) && trim($value) === 'Физическое лицо') {
            return false;
        }

        if (is_numeric($value) && (float) $value === 0.0) {
            return false;
        }

        return true;
    }

    public function contractDate(): ?CarbonImmutable
    {
        return $this->date('contract_date');
    }

    /**
     * The contract's internal billing id — distinct from contractNumber(),
     * which is the human-readable "Договор №" string.
     */
    public function contractId(): ?string
    {
        return $this->string('contract_id');
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

    /**
     * Confirmed live on account 1336708 (2026-08-28): billing sends the
     * tariff's price as `tariff_price` — already in so'm, unlike every
     * CANDIDATE_CURRENT_TARIFF_COST key below, which are tiyin and divided
     * accordingly. The tariff's own name spells this out ("Smart 50 - 125 000
     * сум" alongside `tariff_price: "125000"`) — at tiyin that would be
     * 1 250 so'm, contradicting the name. Checked first because it is the one
     * candidate actually observed on a live account; the rest stay as an
     * untested guess list for accounts that might still report differently.
     */
    public function currentTariffCost(): ?float
    {
        $price = $this->body['tariff_price'] ?? null;

        if (! is_numeric($price)) {
            return $this->firstCost(self::CANDIDATE_CURRENT_TARIFF_COST);
        }

        $price = (float) $price;

        // Unlike nextChargeDate() below, the confirmed field wins here
        // without checking the guess list first — but silently, so a second
        // account where they disagree would ship a wrong "next charge"
        // figure with no signal. This is that signal.
        $legacy = $this->firstCost(self::CANDIDATE_CURRENT_TARIFF_COST);

        if ($legacy !== null && abs($legacy - $price) > 0.01) {
            Log::warning('AbonentProfile: tariff_price disagrees with a legacy tariff-cost field', [
                'tariff_price_som' => $price,
                'legacy_field_som' => $legacy,
            ]);
        }

        return $price;
    }

    /**
     * currentTariff(), with billing's own price suffix trimmed off when it's
     * actually there.
     *
     * Confirmed live 2026-08-28: this billing catalog now names every
     * current tariff "<Plan> - <price> <currency word>" (e.g. "Smart 50 -
     * 125 000 сум", cross-checked against a real /tariff/connected capture
     * showing the same pattern on Smart 75/100/300 too) — so the price this
     * class already reports separately via currentTariffCost() was showing
     * twice on every screen that displays both. Only strips a suffix that
     * literally matches the cost this class already trusts; a name that
     * doesn't end in the known price — including one from a billing
     * generation that never adopted this convention — is returned exactly
     * as billing sent it.
     */
    public function currentTariffDisplayName(): ?string
    {
        return $this->withoutPriceSuffix($this->currentTariff(), $this->currentTariffCost());
    }

    /** nextTariff(), with the same price-suffix trim as currentTariffDisplayName(). */
    public function nextTariffDisplayName(): ?string
    {
        return $this->withoutPriceSuffix($this->nextTariff(), $this->nextTariffCost());
    }

    private function withoutPriceSuffix(?string $name, ?float $cost): ?string
    {
        if ($name === null || $cost === null) {
            return $name;
        }

        // Matched digit-group by digit-group rather than against one
        // hardcoded separator byte: a plain space is what account 1336708's
        // name actually used, but Cyrillic billing exports are also known to
        // use a non-breaking space (U+00A0) as the thousands separator —
        // that would silently fail to match a literal " " and leave the
        // price shown twice again, with no signal that it happened.
        $groups = explode(',', number_format($cost, 0, '', ','));
        $numberPattern = implode('[\s\x{00A0}]', array_map(
            fn (string $group): string => preg_quote($group, '/'),
            $groups,
        ));

        $trimmed = preg_replace(
            '/[\s\x{00A0}]*-[\s\x{00A0}]*'.$numberPattern.'[\s\x{00A0}]*\S+[\s\x{00A0}]*$/u',
            '',
            $name,
        );

        return $trimmed !== null && trim($trimmed) !== '' ? $trimmed : $name;
    }

    public function nextChargeDate(): ?CarbonImmutable
    {
        foreach (self::CANDIDATE_NEXT_CHARGE as $key) {
            if ($date = $this->date($key)) {
                return $date;
            }
        }

        return $this->date('charge_date');
    }

    /*
     * `charge_date` was first read as the LAST charge, with the next one
     * derived from it (individual: +1 month same day; legal entity: end of
     * that month). The client corrected this the same day (2026-08-18): the
     * field IS the upcoming payment date already — billing has already done
     * the individual/legal-entity math on its side. Reading it straight
     * through is the whole rule; no arithmetic belongs here any more, and
     * `isLegalEntity()` above is unrelated to this field now — it only gates
     * the tariff section (TariffController, topbar).
     *
     * There is deliberately no fallback beyond `charge_date`, in the profile.
     * Deriving the charge date from contract_date was tried on 2026-08-10 and
     * reverted the same day: the charge does not fall on the contract day at
     * all, it follows the last tariff change.
     *
     * That change date turned up on 2026-08-13 — /tariff/connected carries it
     * as date_begin — and the client stated a rule the same day: the period
     * ends on the day of the month the tariff started. The derivation therefore
     * lives in App\Support\ConnectedTariff::nextChargeDate(), beside the date it
     * is built from, and CabinetController falls back to it when this method
     * returns null — an account billing has not sent `charge_date` for yet.
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
