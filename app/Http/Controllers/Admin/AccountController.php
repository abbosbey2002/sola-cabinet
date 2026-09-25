<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AccountSearchRequest;
use App\Support\Activity\AccountHistory;
use App\Support\Activity\ActivityRecorder;
use App\Support\Activity\TariffChangeReport;
use App\Support\Admin\AdminAbility;
use App\Support\Admin\CurrentAdmin;
use App\Support\Admin\PersonalData;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;

/**
 * /admin/accounts — find an account by id, contract number or phone and read
 * its whole history: every event and every tariff change, newest first.
 *
 * Personal by nature, so only roles with ViewAccountHistory reach it (the
 * route middleware), and every view of it is in admin_audit.
 */
final class AccountController
{
    public function __construct(
        private readonly AccountHistory $history,
        private readonly TariffChangeReport $changes,
        private readonly CurrentAdmin $admin,
        private readonly ViewFactory $view,
    ) {}

    public function index(AccountSearchRequest $request): View
    {
        $search = $request->search();
        $enabled = ActivityRecorder::isEnabled();

        return $this->view->make('admin.accounts.index', [
            'enabled' => $enabled,
            'search' => $search,
            'accounts' => $enabled && $search !== null ? $this->history->search($search) : collect(),
            'pii' => PersonalData::for($this->admin),
        ]);
    }

    public function show(string $accountId): View
    {
        abort_unless(ActivityRecorder::isEnabled(), 404);

        $latest = $this->history->latest($accountId);
        $tariffChanges = $this->changes->forAccount($accountId);

        abort_if($latest === null && $tariffChanges->isEmpty(), 404);

        return $this->view->make('admin.accounts.show', [
            'accountId' => $accountId,
            'latest' => $latest,
            'events' => $this->history->events($accountId),
            'tariffChanges' => $tariffChanges,
            'pii' => PersonalData::for($this->admin),
            'canSeeTechnical' => $this->admin->can(AdminAbility::ViewTechnicalData),
        ]);
    }
}
