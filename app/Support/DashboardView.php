<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Home dashboard presentation for the three subscriber kinds the cabinet
 * serves: one-off (abonType 1), legal entity, and permanent individual.
 *
 * Views pick a layout partial from {@see kind()} and reuse the shared Blade
 * components under resources/views/components/dashboard/.
 */
final readonly class DashboardView
{
    public const KIND_ONE_TIME = 'one_time';

    public const KIND_LEGAL = 'legal';

    public const KIND_PERMANENT = 'permanent';

    private function __construct(
        public string $kind,
        public float $balance,
        public bool $canTopUp,
        public bool $showTariff,
        public bool $canSwitchTariff,
        public bool $hasTariff,
        public ?float $cost,
        public ?float $currentCost,
        public ?string $state,
        /** @var array{bg: string, fg: string, icon: string}|null */
        public ?array $tone,
        public ?string $note,
        public bool $showCycle,
        public float $ringFraction,
        public string $ringColor,
    ) {}

    public static function make(
        AbonentProfile $profile,
        ?ChargeCycle $cycle,
        bool $isOneTime,
    ): self {
        $kind = match (true) {
            $isOneTime => self::KIND_ONE_TIME,
            $profile->isLegalEntity() => self::KIND_LEGAL,
            default => self::KIND_PERMANENT,
        };

        $showTariff = $kind !== self::KIND_ONE_TIME;
        $canTopUp = config('iwon.active') && $kind !== self::KIND_LEGAL;
        $canSwitchTariff = $showTariff && $kind === self::KIND_PERMANENT;

        $hasTariff = $showTariff && $profile->currentTariff() !== null;

        $cost = $showTariff && $profile->nextTariff() !== null
            ? ($profile->nextTariffCost() ?? $profile->currentTariffCost())
            : ($showTariff ? $profile->currentTariffCost() : null);

        $balance = $profile->balance();

        $state = match (true) {
            $balance < 0 => 'negative',
            $cost !== null && $balance < $cost => 'low',
            $cost !== null => 'ok',
            default => null,
        };

        $tone = match ($state) {
            'ok' => ['bg' => 'var(--c-action-soft)', 'fg' => 'var(--c-action)', 'icon' => 'check'],
            'low' => ['bg' => 'var(--c-warn-soft)', 'fg' => 'var(--c-warn)', 'icon' => 'alert'],
            'negative' => ['bg' => 'var(--c-danger-soft)', 'fg' => 'var(--c-danger)', 'icon' => 'alert'],
            default => null,
        };

        $money = static fn (float $value): string => number_format($value, 0, '', ' ');

        $note = match ($state) {
            'ok' => __('app.dash.balance_ok', [
                'date' => $cycle?->end->format('d.m.Y'),
                'amount' => $money((float) $cost),
            ]),
            'low' => __('app.dash.balance_low', [
                'date' => $cycle?->end->format('d.m.Y'),
                'amount' => $money((float) $cost - $balance),
            ]),
            'negative' => __('app.dash.balance_negative', [
                'amount' => $money(abs($balance) + (float) ($cost ?? 0)),
            ]),
            default => null,
        };

        $showCycle = $hasTariff && $cycle !== null;
        $ringFraction = $showCycle ? $cycle->daysLeft / $cycle->totalDays : 0.0;
        $ringColor = $tone['fg'] ?? 'var(--c-action)';

        return new self(
            kind: $kind,
            balance: $balance,
            canTopUp: $canTopUp,
            showTariff: $showTariff,
            canSwitchTariff: $canSwitchTariff,
            hasTariff: $hasTariff,
            cost: $cost,
            currentCost: $profile->currentTariffCost(),
            state: $state,
            tone: $tone,
            note: $note,
            showCycle: $showCycle,
            ringFraction: $ringFraction,
            ringColor: $ringColor,
        );
    }

    public function formatMoney(float $value): string
    {
        return number_format($value, 0, '', ' ');
    }

    public function formatSigned(float $value): string
    {
        return str_replace('-', '−', $this->formatMoney($value));
    }

    /** @return positive-int */
    public function metricColumnCount(): int
    {
        return $this->showTariff ? 4 : 3;
    }
}
