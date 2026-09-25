<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Support\Activity\ReportPeriod;
use App\Support\Activity\TariffChangeFilter;
use App\Support\Activity\TariffChangeResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The /admin/tariff-changes filter bar (and its CSV export, which takes the
 * same query string).
 */
final class TariffChangeFilterRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'result' => ['nullable', 'string', Rule::in(TariffChangeResult::values())],
            'old_tariff' => ['nullable', 'string', 'regex:/^\d{1,16}$/'],
            'new_tariff' => ['nullable', 'string', 'regex:/^\d{1,16}$/'],
            'q' => ['nullable', 'string', 'max:64'],
            'abon_type' => ['nullable', 'integer', 'min:0', 'max:99'],
        ];
    }

    public function filter(): TariffChangeFilter
    {
        $search = trim((string) $this->validated('q'));
        $abonType = $this->validated('abon_type');

        return new TariffChangeFilter(
            ReportPeriod::between($this->validated('from'), $this->validated('to')),
            TariffChangeResult::tryFrom((string) $this->validated('result')),
            $this->validated('old_tariff'),
            $this->validated('new_tariff'),
            $search === '' ? null : $search,
            $abonType === null ? null : (int) $abonType,
        );
    }

    /** Back to the unfiltered list — see ReportPeriodRequest::getRedirectUrl(). */
    protected function getRedirectUrl(): string
    {
        return route('admin.tariff-changes');
    }
}
