<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Support\Activity\ActivityStats;
use App\Support\Activity\ReportPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The date range (and, on /admin/stats/segments, the grouping column) of a
 * statistics page. A bad value sends the admin back to the page's defaults —
 * never back to the same bad URL, which would redirect forever.
 */
final class ReportPeriodRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'by' => ['nullable', 'string', Rule::in(ActivityStats::SEGMENTS)],
        ];
    }

    public function period(): ReportPeriod
    {
        return ReportPeriod::between($this->validated('from'), $this->validated('to'));
    }

    public function segmentColumn(): string
    {
        return (string) ($this->validated('by') ?? 'device_type');
    }

    protected function getRedirectUrl(): string
    {
        return $this->url();
    }
}
