<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\UsesActivityDatabase;
use Tests\TestCase;

/**
 * The admin side of the activity journal: who may open what (plan §7), what
 * each role sees of a subscriber, the CSV export and the admin audit trail.
 */
final class ActivityAdminTest extends TestCase
{
    // admins lives in the shared local sqlite file.
    use DatabaseTransactions;
    use UsesActivityDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        // /admin/tariffs reads the catalogue from billing.
        Http::fake(['*/tariff/available' => Http::response(['tariffs' => []])]);
        $this->setUpActivityDatabase();
        $this->seedTariffChange();
    }

    /**
     * @return iterable<string, array{string, string, int}>
     */
    public static function accessMatrix(): iterable
    {
        $screens = [
            'stats' => '/admin/stats',
            'funnel' => '/admin/stats/funnel',
            'segments' => '/admin/stats/segments',
            'errors' => '/admin/stats/errors',
            'tariff changes' => '/admin/tariff-changes',
            'tariff change' => '/admin/tariff-changes/1',
            'export' => '/admin/tariff-changes/export',
            'accounts' => '/admin/accounts?q=1003',
            'account' => '/admin/accounts/1003',
            'tariffs' => '/admin/tariffs',
        ];

        $forbidden = [
            'admin' => [],
            'analyst' => ['accounts', 'account', 'tariffs'],
            'sales' => ['tariffs'],
        ];

        foreach ($forbidden as $role => $denied) {
            foreach ($screens as $screen => $path) {
                yield "{$role} → {$screen}" => [$role, $path, in_array($screen, $denied, true) ? 403 : 200];
            }
        }
    }

    #[Test]
    #[DataProvider('accessMatrix')]
    public function each_role_opens_only_its_own_screens(string $role, string $path, int $status): void
    {
        $this->asAdmin($role)->get($path)->assertStatus($status);
    }

    #[Test]
    public function an_admin_with_an_unknown_role_gets_nothing(): void
    {
        $this->asAdmin('root')->get('/admin/stats')->assertForbidden();
    }

    #[Test]
    public function a_deleted_admins_cookie_stops_working_on_the_next_request(): void
    {
        $this->withCookie('admin', '999999')
            ->get('/admin/stats')
            ->assertRedirect(route('admin.login'))
            ->assertCookieExpired('admin');
    }

    #[Test]
    public function each_role_lands_on_the_first_screen_it_may_open(): void
    {
        $username = 'login-'.uniqid();
        $this->seedAdmin('analyst', $username);

        $this->post(route('admin.login'), ['username' => $username, 'password' => 'correct-password'])
            ->assertRedirect(route('admin.stats'));
    }

    #[Test]
    public function the_analyst_sees_phones_and_names_masked_everywhere(): void
    {
        $analyst = $this->asAdmin('analyst');

        $analyst->get('/admin/tariff-changes')
            ->assertOk()
            ->assertSee('+998 93 *** ** 11')
            ->assertSee('R***** A*****')
            ->assertDontSee('998935550011')
            ->assertDontSee('Rustam Aliyev');

        // The raw billing snapshot is technical detail the analyst may read —
        // but not a way around the mask.
        $analyst->get('/admin/tariff-changes/1')
            ->assertOk()
            ->assertSee(__('admin.tc.snapshot'))
            ->assertSee('3200.50')
            ->assertDontSee('998935550011')
            ->assertDontSee('Rustam Aliyev')
            ->assertDontSee('rustam@example.uz');
    }

    #[Test]
    public function sales_sees_the_subscriber_in_full_but_no_technical_detail(): void
    {
        $this->asAdmin('sales')->get('/admin/tariff-changes/1')
            ->assertOk()
            ->assertSee('Rustam Aliyev')
            ->assertSee('+998 93 555 00 11')
            ->assertDontSee('203.0.113.9')
            ->assertDontSee(__('admin.tc.snapshot'));
    }

    #[Test]
    public function the_export_is_masked_for_the_analyst_and_raw_for_sales(): void
    {
        $analystCsv = $this->asAdmin('analyst')->get('/admin/tariff-changes/export')->assertOk()->streamedContent();
        $salesCsv = $this->asAdmin('sales')->get('/admin/tariff-changes/export')->assertOk()->streamedContent();

        $this->assertStringContainsString('+998 93 *** ** 11', $analystCsv);
        $this->assertStringNotContainsString('998935550011', $analystCsv);
        // The analyst may see technical columns; sales may not.
        $this->assertStringContainsString('user_agent', strtok($analystCsv, "\n"));

        $this->assertStringContainsString('998935550011', $salesCsv);
        $this->assertStringNotContainsString('user_agent', strtok($salesCsv, "\n"));
    }

    #[Test]
    public function the_export_never_hands_a_spreadsheet_a_formula(): void
    {
        $csv = $this->asAdmin('admin')->get('/admin/tariff-changes/export')->streamedContent();

        // new_tariff_name came from billing as =HYPERLINK(...): quoted as text
        // (the CSV itself doubles the inner quotes).
        $this->assertStringContainsString("\"'=HYPERLINK(\"\"http://x\"\")\"", $csv);
        $this->assertStringNotContainsString(',"=HYPERLINK', $csv);
    }

    #[Test]
    public function every_admin_request_is_audited_including_the_refused_ones(): void
    {
        $analyst = $this->asAdmin('analyst');

        $analyst->get('/admin/tariff-changes?q=1003&result=insufficient_funds')->assertOk();
        $analyst->get('/admin/accounts/1003')->assertForbidden();

        $audit = $this->activity()->table('admin_audit')->orderBy('id')->get();

        $this->assertCount(2, $audit);
        $this->assertSame('analyst', $audit[0]->admin_role);
        $this->assertSame('admin.tariff-changes', $audit[0]->action);
        $this->assertSame(['q' => '1003', 'result' => 'insufficient_funds'], json_decode((string) $audit[0]->query, true));
        $this->assertSame('admin.accounts.show', $audit[1]->action);
        $this->assertSame('1003', $audit[1]->subject_account_id);
        $this->assertSame(403, (int) $audit[1]->http_status);
    }

    #[Test]
    public function the_tariff_change_list_filters_by_result_and_searches_by_phone(): void
    {
        $admin = $this->asAdmin('admin');

        $admin->get('/admin/tariff-changes?result=success')->assertOk()->assertSee(__('admin.tc.empty'));
        $admin->get('/admin/tariff-changes?q=5550011')->assertOk()->assertSee('Rustam Aliyev');
        $admin->get('/admin/tariff-changes?q=0000000')->assertOk()->assertDontSee('Rustam Aliyev');
    }

    #[Test]
    public function a_bad_filter_sends_the_admin_back_to_the_plain_list_not_into_a_loop(): void
    {
        $this->asAdmin('admin')
            ->get('/admin/tariff-changes?result=hacked')
            ->assertRedirect(route('admin.tariff-changes'));
    }

    #[Test]
    public function the_statistics_pages_say_so_when_the_journal_is_off(): void
    {
        config(['activity.enabled' => false]);

        $this->asAdmin('analyst')->get('/admin/stats')->assertOk()->assertSee(__('admin.journal_off.title'));
        $this->asAdmin('analyst')->get('/admin/tariff-changes')->assertOk()->assertSee(__('admin.journal_off.title'));
    }

    #[Test]
    public function the_account_history_shows_what_the_account_did(): void
    {
        $this->activity()->table('activity_events')->insert([
            'occurred_at' => CarbonImmutable::now('UTC')->format('Y-m-d H:i:s'),
            'occurred_on' => CarbonImmutable::now('UTC')->format('Y-m-d'),
            'event' => 'ui.search',
            'outcome' => 'ok',
            'account_id' => '1003',
            'phone' => '998935550011',
            'full_name' => 'Rustam Aliyev',
            'meta' => json_encode(['query' => 'Payme']),
        ]);

        $this->asAdmin('sales')->get('/admin/accounts?q=5550011')->assertOk()->assertSee('1003');

        $this->asAdmin('sales')->get('/admin/accounts/1003')
            ->assertOk()
            ->assertSee(__('admin.events.ui_search'))
            ->assertSee('query: Payme')
            ->assertSee('Turbo 200');
    }

    private function asAdmin(string $role): self
    {
        $id = $this->seedAdmin($role, 'qa-'.$role.'-'.uniqid());

        return $this->withCookie('admin', (string) $id);
    }

    private function seedAdmin(string $role, string $username): int
    {
        return (int) DB::table('admins')->insertGetId([
            'username' => $username,
            'password' => Hash::make('correct-password'),
            'role' => $role,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedTariffChange(): void
    {
        $now = CarbonImmutable::now('UTC');

        $this->activity()->table('tariff_changes')->insert([
            'created_at' => $now->format('Y-m-d H:i:s'),
            'updated_at' => $now->format('Y-m-d H:i:s'),
            'account_id' => '1003',
            'billing_login' => '998935550011',
            'phone' => '998935550011',
            'full_name' => 'Rustam Aliyev',
            'email' => 'rustam@example.uz',
            'abon_type' => 2,
            'balance_before' => '3200.50',
            'old_tariff_id' => '9',
            'old_tariff_name' => 'Paket 30 kun',
            'old_tariff_price' => '12000.00',
            'new_tariff_id' => '412',
            'new_tariff_name' => '=HYPERLINK("http://x")',
            'new_tariff_price' => '45000.00',
            'timing' => 'now',
            'result' => 'insufficient_funds',
            'error_code' => '129',
            'error_message' => 'Баланс не позволяет изменить тариф',
            'ip' => '203.0.113.9',
            'user_agent' => 'Mozilla/5.0',
            'profile_snapshot' => json_encode([
                'name' => 'Rustam Aliyev',
                'phone' => '998935550011',
                'email' => 'rustam@example.uz',
                'saldo' => '3200.50',
            ], JSON_UNESCAPED_UNICODE),
        ]);

        // A second attempt with a plain name, for the account-history page.
        $this->activity()->table('tariff_changes')->insert([
            'created_at' => $now->subDay()->format('Y-m-d H:i:s'),
            'updated_at' => $now->subDay()->format('Y-m-d H:i:s'),
            'account_id' => '1003',
            'phone' => '998935550011',
            'full_name' => 'Rustam Aliyev',
            'new_tariff_id' => '412',
            'new_tariff_name' => 'Turbo 200',
            'result' => 'denied',
            'denied_reason' => 'not_allowed',
        ]);
    }
}
