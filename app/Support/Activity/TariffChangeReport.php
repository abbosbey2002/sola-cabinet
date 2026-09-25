<?php

declare(strict_types=1);

namespace App\Support\Activity;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

/**
 * The queries behind /admin/tariff-changes: the filtered list, the summary
 * cards, the most-chosen tariffs and the "from → to" pairs.
 */
final class TariffChangeReport
{
    public const PER_PAGE = 50;

    /** @return LengthAwarePaginator<int, object> */
    public function paginate(TariffChangeFilter $filter): LengthAwarePaginator
    {
        return $this->filtered($filter)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    public function find(int $id): ?object
    {
        return $this->db()->table('tariff_changes')->where('id', $id)->first();
    }

    /**
     * Every matching row, streamed for the CSV export — never loaded at once.
     *
     * @return LazyCollection<int, object>
     */
    public function cursor(TariffChangeFilter $filter): LazyCollection
    {
        return $this->filtered($filter)->lazyById(1000);
    }

    /**
     * @return array{attempts: int, succeeded: int, success_rate: ?float, insufficient_funds: int, accounts: int}
     */
    public function summary(TariffChangeFilter $filter): array
    {
        $row = $this->filtered($filter)
            ->selectRaw('COUNT(*) AS attempts')
            ->selectRaw("SUM(CASE WHEN result = 'success' THEN 1 ELSE 0 END) AS succeeded")
            ->selectRaw("SUM(CASE WHEN result = 'insufficient_funds' THEN 1 ELSE 0 END) AS insufficient_funds")
            ->selectRaw('COUNT(DISTINCT account_id) AS accounts')
            ->first();

        $attempts = (int) ($row->attempts ?? 0);
        $succeeded = (int) ($row->succeeded ?? 0);

        return [
            'attempts' => $attempts,
            'succeeded' => $succeeded,
            'success_rate' => $attempts > 0 ? round($succeeded * 100 / $attempts, 1) : null,
            'insufficient_funds' => (int) ($row->insufficient_funds ?? 0),
            'accounts' => (int) ($row->accounts ?? 0),
        ];
    }

    /**
     * @return Collection<int, object{new_tariff_id: string, new_tariff_name: ?string, attempts: int, succeeded: int}>
     */
    public function topTariffs(TariffChangeFilter $filter, int $limit = 10): Collection
    {
        return $this->filtered($filter)
            ->groupBy('new_tariff_id')
            ->select('new_tariff_id')
            ->selectRaw('MAX(new_tariff_name) AS new_tariff_name')
            ->selectRaw('COUNT(*) AS attempts')
            ->selectRaw("SUM(CASE WHEN result = 'success' THEN 1 ELSE 0 END) AS succeeded")
            ->orderByDesc('attempts')
            ->limit($limit)
            ->get()
            ->map(fn (object $row): object => (object) [
                'new_tariff_id' => (string) $row->new_tariff_id,
                'new_tariff_name' => $row->new_tariff_name === null ? null : (string) $row->new_tariff_name,
                'attempts' => (int) $row->attempts,
                'succeeded' => (int) $row->succeeded,
            ]);
    }

    /**
     * Which tariff people move from and to — the matrix, as its non-empty cells.
     *
     * @return Collection<int, object{old_tariff_name: ?string, new_tariff_name: ?string, attempts: int, succeeded: int}>
     */
    public function transitions(TariffChangeFilter $filter, int $limit = 20): Collection
    {
        return $this->filtered($filter)
            ->groupBy('old_tariff_id', 'new_tariff_id')
            ->select('old_tariff_id', 'new_tariff_id')
            ->selectRaw('MAX(old_tariff_name) AS old_tariff_name')
            ->selectRaw('MAX(new_tariff_name) AS new_tariff_name')
            ->selectRaw('COUNT(*) AS attempts')
            ->selectRaw("SUM(CASE WHEN result = 'success' THEN 1 ELSE 0 END) AS succeeded")
            ->orderByDesc('attempts')
            ->limit($limit)
            ->get()
            ->map(fn (object $row): object => (object) [
                'old_tariff_name' => $row->old_tariff_name === null ? null : (string) $row->old_tariff_name,
                'new_tariff_name' => $row->new_tariff_name === null ? null : (string) $row->new_tariff_name,
                'attempts' => (int) $row->attempts,
                'succeeded' => (int) $row->succeeded,
            ]);
    }

    /**
     * The tariffs seen in the history, for the filter's drop-downs.
     *
     * @return array<string, string> id => name
     */
    public function knownTariffs(): array
    {
        $old = $this->db()->table('tariff_changes')
            ->whereNotNull('old_tariff_id')
            ->groupBy('old_tariff_id')
            ->select('old_tariff_id AS id')
            ->selectRaw('MAX(old_tariff_name) AS name');

        return $this->db()->table('tariff_changes')
            ->groupBy('new_tariff_id')
            ->select('new_tariff_id AS id')
            ->selectRaw('MAX(new_tariff_name) AS name')
            ->union($old)
            ->get()
            ->mapWithKeys(fn (object $row): array => [(string) $row->id => trim((string) ($row->name ?? '')) ?: '#'.$row->id])
            ->sort()
            ->all();
    }

    /**
     * @return Collection<int, object>
     */
    public function forAccount(string $accountId, int $limit = 100): Collection
    {
        return $this->db()->table('tariff_changes')
            ->where('account_id', $accountId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    private function filtered(TariffChangeFilter $filter): Builder
    {
        return $this->db()->table('tariff_changes')
            ->whereBetween('created_at', [$filter->period->startsAt(), $filter->period->endsAt()])
            ->when($filter->result !== null, fn (Builder $query) => $query->where('result', $filter->result?->value))
            ->when($filter->oldTariffId !== null, fn (Builder $query) => $query->where('old_tariff_id', $filter->oldTariffId))
            ->when($filter->newTariffId !== null, fn (Builder $query) => $query->where('new_tariff_id', $filter->newTariffId))
            ->when($filter->abonType !== null, fn (Builder $query) => $query->where('abon_type', $filter->abonType))
            ->when($filter->search !== null, fn (Builder $query) => $this->search($query, (string) $filter->search));
    }

    /** Exact account id or contract number, or phone digits anywhere in the number. */
    private function search(Builder $query, string $search): void
    {
        $digits = preg_replace('/\D/', '', $search) ?? '';

        $query->where(function (Builder $where) use ($search, $digits): void {
            $where->where('account_id', $search)->orWhere('billing_login', $search);

            if (strlen($digits) >= 4) {
                $where->orWhere('phone', 'like', '%'.$digits.'%');
            }
        });
    }

    private function db(): ConnectionInterface
    {
        return DB::connection(config('activity.connection'));
    }
}
