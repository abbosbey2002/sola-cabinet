<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The admin panel's own login, separate from the subscriber's SMS flow — see
 * app/Support/AdminSession.php. Wrapped in a transaction: the "admins" table
 * lives in the same sqlite file as local dev, so a test insert must never
 * survive past the test that made it.
 */
final class AdminAuthTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    #[Test]
    public function the_tariff_dashboard_is_closed_without_a_login(): void
    {
        $this->get(route('admin.tariffs'))->assertRedirect(route('admin.login'));
    }

    #[Test]
    public function a_wrong_password_does_not_sign_in(): void
    {
        $this->seedAdmin();

        $response = $this->post(route('admin.login'), [
            'username' => 'test-admin',
            'password' => 'not-the-password',
        ]);

        $response->assertOk();
        $response->assertSee(trans('app.admin.login_failed'));
        $response->assertCookieMissing('admin');
    }

    #[Test]
    public function an_unknown_username_does_not_sign_in(): void
    {
        // Same generic error as a wrong password — see the comment in
        // AdminAuthController::login() on why a username is never confirmed.
        $response = $this->post(route('admin.login'), [
            'username' => 'nobody',
            'password' => 'whatever',
        ]);

        $response->assertOk();
        $response->assertSee(trans('app.admin.login_failed'));
        $response->assertCookieMissing('admin');
    }

    #[Test]
    public function the_right_password_signs_in(): void
    {
        $adminId = $this->seedAdmin();

        $response = $this->post(route('admin.login'), [
            'username' => 'test-admin',
            'password' => 'correct-password',
        ]);

        $response->assertRedirect(route('admin.tariffs'));
        $response->assertCookie('admin', (string) $adminId);
    }

    #[Test]
    public function a_signed_in_admin_is_sent_away_from_the_login_screen(): void
    {
        $adminId = $this->seedAdmin();

        $this->authenticatedAdmin($adminId)
            ->get(route('admin.login'))
            ->assertRedirect(route('admin.tariffs'));
    }

    #[Test]
    public function logging_out_expires_the_admin_cookie(): void
    {
        $adminId = $this->seedAdmin();

        // The test client's default cookies are fixed for its lifetime — they
        // do not react to a Set-Cookie response the way a browser jar would
        // (see AuthTest.php's equivalent subscriber-logout test), so what is
        // provable here is that the response itself expires the cookie, which
        // is what makes a real browser drop it on the next request.
        $this->authenticatedAdmin($adminId)
            ->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'))
            ->assertCookieExpired('admin');
    }

    private function seedAdmin(string $username = 'test-admin', string $password = 'correct-password'): int
    {
        return (int) DB::table('admins')->insertGetId([
            'username' => $username,
            'password' => Hash::make($password),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function authenticatedAdmin(int $adminId): self
    {
        return $this->withCookies(['admin' => (string) $adminId]);
    }
}
