<?php

declare(strict_types=1);

namespace App\Support\Activity;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * /admin/accounts: find an account, then read everything it did.
 */
final class AccountHistory
{
    public const PER_PAGE = 100;

    /**
     * Accounts matching an account id, contract number or phone digits — the
     * latest known name and phone for each, and when it was last seen.
     *
     * @return Collection<int, object{account_id: string, billing_login: ?string, phone: ?string, full_name: ?string, events: int, last_seen: string}>
     */
    public function search(string $search, int $limit = 50): Collection
    {
        $digits = preg_replace('/\D/', '', $search) ?? '';

        return $this->db()->table('activity_events')
            ->whereNotNull('account_id')
            ->where(function (Builder $where) use ($search, $digits): void {
                $where->where('account_id', $search)->orWhere('billing_login', $search);

                if (strlen($digits) >= 4) {
                    $where->orWhere('phone', 'like', '%'.$digits.'%');
                }
            })
            ->groupBy('account_id')
            ->select('account_id')
            ->selectRaw('MAX(billing_login) AS billing_login')
            ->selectRaw('MAX(phone) AS phone')
            ->selectRaw('MAX(full_name) AS full_name')
            ->selectRaw('COUNT(*) AS events')
            ->selectRaw('MAX(occurred_at) AS last_seen')
            ->orderByDesc('last_seen')
            ->limit($limit)
            ->get()
            ->map(fn (object $row): object => (object) [
                'account_id' => (string) $row->account_id,
                'billing_login' => $row->billing_login === null ? null : (string) $row->billing_login,
                'phone' => $row->phone === null ? null : (string) $row->phone,
                'full_name' => $row->full_name === null ? null : (string) $row->full_name,
                'events' => (int) $row->events,
                'last_seen' => (string) $row->last_seen,
            ]);
    }

    /** The account's most recent row — who it is, as last seen. */
    public function latest(string $accountId): ?object
    {
        return $this->db()->table('activity_events')
            ->where('account_id', $accountId)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->first();
    }

    /** @return LengthAwarePaginator<int, object> */
    public function events(string $accountId): LengthAwarePaginator
    {
        return $this->db()->table('activity_events')
            ->where('account_id', $accountId)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    private function db(): ConnectionInterface
    {
        return DB::connection(config('activity.connection'));
    }
}
