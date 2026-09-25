<?php

declare(strict_types=1);

namespace App\Support\Activity;

/**
 * The closed catalogue of things the activity journal records.
 *
 * Closed on purpose: the statistics pages group by this value, and the
 * browser beacon (POST /activity) is only allowed to send the four `ui.*`
 * names below — a free-form event name from the client would let anyone
 * fill the table with noise.
 */
enum ActivityEvent: string
{
    // Menu: a cabinet page was opened.
    case PageHome = 'page.home';
    case PageTariffs = 'page.tariffs';
    case PageDevices = 'page.devices';
    case PageStatistics = 'page.statistics';
    case PageFinance = 'page.finance';
    case PageServices = 'page.services';
    case PageTopUp = 'page.topup';

    // Server actions.
    case AuthSmsRequested = 'auth.sms_requested';
    case AuthVerified = 'auth.verified';
    case AuthAccountSelected = 'auth.account_selected';
    case AuthAccountSwitched = 'auth.account_switched';
    case AuthLogout = 'auth.logout';
    case TariffConnect = 'tariff.connect';
    case DeviceAdd = 'device.add';
    case DeviceDelete = 'device.delete';
    case TopUpInitiated = 'topup.initiated';
    case FilterStatistics = 'filter.statistics';
    case FilterFinance = 'filter.finance';
    case LocaleChanged = 'locale.changed';
    case RateLimited = 'rate_limited';

    // Browser actions, sent by resources/js/modules/activity.js.
    case UiPrint = 'ui.print';
    case UiSearch = 'ui.search';
    case UiTariffDialogCancelled = 'ui.tariff_dialog_cancelled';
    case UiDeviceDialogCancelled = 'ui.device_dialog_cancelled';

    /**
     * The event a cabinet route stands for — the one map RecordActivity reads.
     * Routes not listed here are not recorded (the beacon records itself).
     */
    public static function forRoute(?string $routeName): ?self
    {
        return match ($routeName) {
            'cabinet' => self::PageHome,
            'tariff' => self::PageTariffs,
            'devices' => self::PageDevices,
            'traffic' => self::PageStatistics,
            'payment' => self::PageFinance,
            'services' => self::PageServices,
            'topup' => self::PageTopUp,
            'login.submit' => self::AuthSmsRequested,
            'verify.submit' => self::AuthVerified,
            'set.account' => self::AuthAccountSwitched,
            'logout' => self::AuthLogout,
            'tariff.connect' => self::TariffConnect,
            'devices.add' => self::DeviceAdd,
            'devices.delete' => self::DeviceDelete,
            'topup.store' => self::TopUpInitiated,
            'traffic.filter' => self::FilterStatistics,
            'payment.filter' => self::FilterFinance,
            'change.lang' => self::LocaleChanged,
            default => null,
        };
    }

    /** @return list<string> */
    public static function browserValues(): array
    {
        return array_values(array_map(
            fn (self $event): string => $event->value,
            array_filter(self::cases(), fn (self $event): bool => $event->isBrowser()),
        ));
    }

    public function isBrowser(): bool
    {
        return str_starts_with($this->value, 'ui.');
    }

    public function isPage(): bool
    {
        return str_starts_with($this->value, 'page.');
    }

    /** Actions that spend the subscriber's money or attach hardware. */
    public function spendsMoney(): bool
    {
        return in_array($this, [self::TariffConnect, self::DeviceAdd, self::TopUpInitiated], true);
    }
}
