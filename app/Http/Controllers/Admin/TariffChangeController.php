<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\TariffChangeFilterRequest;
use App\Support\Activity\ActivityRecorder;
use App\Support\Activity\TariffChangeReport;
use App\Support\Activity\TariffChangeResult;
use App\Support\Admin\AdminAbility;
use App\Support\Admin\CurrentAdmin;
use App\Support\Admin\PersonalData;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * /admin/tariff-changes — every tariff change attempt with the account as it
 * was, the old and the new tariff, and what came of it (plan §4.3).
 *
 * Phone and name are masked for a role without ViewPersonalData; IP, user
 * agent and the raw billing snapshot are shown only with ViewTechnicalData.
 */
final class TariffChangeController
{
    public function __construct(
        private readonly TariffChangeReport $report,
        private readonly CurrentAdmin $admin,
        private readonly ViewFactory $view,
    ) {}

    public function index(TariffChangeFilterRequest $request): View
    {
        $filter = $request->filter();
        $enabled = ActivityRecorder::isEnabled();

        return $this->view->make('admin.tariff-changes.index', [
            'filter' => $filter,
            'enabled' => $enabled,
            'changes' => $enabled ? $this->report->paginate($filter) : null,
            'summary' => $enabled ? $this->report->summary($filter) : null,
            'topTariffs' => $enabled ? $this->report->topTariffs($filter) : collect(),
            'transitions' => $enabled ? $this->report->transitions($filter) : collect(),
            'knownTariffs' => $enabled ? $this->report->knownTariffs() : [],
            'results' => TariffChangeResult::cases(),
            'pii' => PersonalData::for($this->admin),
            'canExport' => $this->admin->can(AdminAbility::Export),
            'canOpenAccount' => $this->admin->can(AdminAbility::ViewAccountHistory),
        ]);
    }

    public function show(int $changeId): View
    {
        abort_unless(ActivityRecorder::isEnabled(), 404);

        $change = $this->report->find($changeId);

        abort_if($change === null, 404);

        $canSeeTechnical = $this->admin->can(AdminAbility::ViewTechnicalData);
        $pii = PersonalData::for($this->admin);

        return $this->view->make('admin.tariff-changes.show', [
            'change' => $change,
            'pii' => $pii,
            'canSeeTechnical' => $canSeeTechnical,
            'canOpenAccount' => $this->admin->can(AdminAbility::ViewAccountHistory),
            'snapshot' => $canSeeTechnical ? self::snapshot($change->profile_snapshot ?? null, $pii) : null,
        ]);
    }

    /**
     * The filtered list as CSV, streamed row by row. Masked exactly as the
     * screen is; the export itself lands in admin_audit with its filters.
     */
    public function export(TariffChangeFilterRequest $request): StreamedResponse
    {
        abort_unless(ActivityRecorder::isEnabled(), 404);

        $filter = $request->filter();
        $pii = PersonalData::for($this->admin);
        $technical = $this->admin->can(AdminAbility::ViewTechnicalData);

        $columns = [
            'id', 'created_at', 'result', 'error_code', 'error_message', 'denied_reason',
            'account_id', 'billing_login', 'phone', 'full_name', 'abon_type', 'is_legal_entity',
            'balance_before', 'balance_after', 'next_charge_date',
            'old_tariff_id', 'old_tariff_name', 'old_tariff_price',
            'new_tariff_id', 'new_tariff_name', 'new_tariff_price',
            'timing', 'effective_date', 'pending_tariff_after', 'billing_duration_ms', 'locale', 'device_type',
        ];

        if ($technical) {
            array_push($columns, 'ip', 'user_agent');
        }

        $filename = sprintf('tariff-changes_%s_%s.csv', $filter->period->fromDate(), $filter->period->toDate());

        return response()->streamDownload(function () use ($filter, $pii, $columns): void {
            $out = fopen('php://output', 'wb');

            if ($out === false) {
                return;
            }

            // A BOM, so Excel opens the Cyrillic tariff names as UTF-8.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $columns, escape: '');

            foreach ($this->report->cursor($filter) as $change) {
                $row = (array) $change;

                // Raw digits for a role that may see them — the sales team
                // imports this file into a dialler — masked for everyone else.
                if (! $pii->isUnmasked()) {
                    $row['phone'] = $pii->phone($row['phone'] ?? null);
                    $row['full_name'] = $pii->name($row['full_name'] ?? null);
                    $row['billing_login'] = $pii->login($row['billing_login'] ?? null);
                }

                fputcsv($out, array_map(
                    fn (string $column): string => self::csvCell($row[$column] ?? null),
                    $columns,
                ), escape: '');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * A cell a spreadsheet will not execute: a value starting with = + - @
     * is prefixed with a quote (CSV formula injection). Tariff names and
     * billing error text are not ours to trust.
     */
    private static function csvCell(mixed $value): string
    {
        $text = match (true) {
            $value === null => '',
            is_bool($value) => $value ? '1' : '0',
            default => (string) $value,
        };

        return preg_match('/^[=+\-@\t\r]/', $text) === 1 && ! is_numeric($text) ? "'".$text : $text;
    }

    /**
     * The raw /abonent/info as it was, pretty-printed — with the subscriber's
     * contact fields masked for a role that may see technical detail but not
     * personal data (the analyst), so the snapshot is not a way around the mask.
     */
    private static function snapshot(mixed $json, PersonalData $pii): ?string
    {
        if (! is_string($json) || $json === '') {
            return null;
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return null;
        }

        if (! $pii->isUnmasked()) {
            foreach ($decoded as $key => $value) {
                if (! is_scalar($value)) {
                    continue;
                }

                $decoded[$key] = match ($key) {
                    'phone' => $pii->phone((string) $value),
                    'name' => $pii->name((string) $value),
                    'email', 'address' => '***',
                    default => $value,
                };
            }
        }

        return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: null;
    }
}
