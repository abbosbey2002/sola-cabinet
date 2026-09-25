<?php

declare(strict_types=1);

namespace App\Support\Activity;

/**
 * The /admin/tariff-changes filter bar, already validated
 * (App\Http\Requests\Admin\TariffChangeFilterRequest).
 */
final readonly class TariffChangeFilter
{
    public function __construct(
        public ReportPeriod $period,
        public ?TariffChangeResult $result = null,
        public ?string $oldTariffId = null,
        public ?string $newTariffId = null,
        /** Account id, contract number (billing login) or phone digits. */
        public ?string $search = null,
        public ?int $abonType = null,
    ) {}

    /**
     * The filter as query-string values, for pagination and the export link.
     *
     * @return array<string, string>
     */
    public function toQuery(): array
    {
        return array_filter([
            'from' => $this->period->fromDate(),
            'to' => $this->period->toDate(),
            'result' => $this->result?->value,
            'old_tariff' => $this->oldTariffId,
            'new_tariff' => $this->newTariffId,
            'q' => $this->search,
            'abon_type' => $this->abonType === null ? null : (string) $this->abonType,
        ], fn (?string $value): bool => $value !== null && $value !== '');
    }
}
