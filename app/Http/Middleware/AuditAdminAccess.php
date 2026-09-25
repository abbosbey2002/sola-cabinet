<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Activity\ActivityRecorder;
use App\Support\Admin\CurrentAdmin;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * admin_audit: every admin-panel request by a signed-in admin — which page,
 * which filters, whose account history, which export. Access to subscribers'
 * personal data must not be traceless (plan §7).
 *
 * Written after the response, like the activity journal, and never allowed to
 * break the page. Only while the journal itself is switched on.
 */
final class AuditAdminAccess
{
    /** Filter values are short; anything longer is not a filter. */
    private const QUERY_VALUE_LIMIT = 100;

    public function __construct(private readonly CurrentAdmin $admin) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $identity = $this->admin->identity();

        if ($identity === null || ! ActivityRecorder::isEnabled()) {
            return $response;
        }

        $accountId = $request->route('accountId');

        $row = [
            'created_at' => CarbonImmutable::now('UTC')->format('Y-m-d H:i:s'),
            'admin_id' => $identity->id,
            'admin_username' => mb_substr($identity->username, 0, 255),
            'admin_role' => $identity->role->value ?? 'unknown',
            'action' => mb_substr((string) ($request->route()?->getName() ?? 'unknown'), 0, 64),
            'method' => $request->method(),
            'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 255),
            'query' => self::query($request),
            'subject_account_id' => is_string($accountId) ? mb_substr($accountId, 0, 32) : null,
            'http_status' => $response->getStatusCode(),
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 512) ?: null,
        ];

        defer(function () use ($row): void {
            try {
                DB::connection(config('activity.connection'))->table('admin_audit')->insert($row);
            } catch (Throwable $e) {
                Log::warning('activity: admin access not audited', [
                    'admin_id' => $row['admin_id'],
                    'action' => $row['action'],
                    'reason' => $e->getMessage(),
                ]);
            }
        })->always(); // a 403 on a screen the role may not open is worth auditing too

        return $response;
    }

    private static function query(Request $request): ?string
    {
        $query = collect($request->query())
            ->filter(fn (mixed $value): bool => is_scalar($value))
            ->map(fn (mixed $value): string => mb_substr((string) $value, 0, self::QUERY_VALUE_LIMIT))
            ->take(20)
            ->all();

        return $query === [] ? null : (json_encode($query, JSON_UNESCAPED_UNICODE) ?: null);
    }
}
