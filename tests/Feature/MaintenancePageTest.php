<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `scripts/deploy.sh` calls `php artisan down --render=errors::503`. That
 * compiles the guest layout with no HTTP session, so `$errors` is missing.
 * If this view throws, deploy aborts before `npm run build` and production
 * keeps a stale Vite manifest against new Blade.
 */
final class MaintenancePageTest extends TestCase
{
    #[Test]
    public function the_maintenance_page_renders_without_a_session_error_bag(): void
    {
        $html = view('errors.503')->render();

        $this->assertStringContainsString(__('errors.service_unavailable'), $html);
    }

    #[Test]
    public function artisan_down_can_compile_the_custom_503(): void
    {
        try {
            $this->artisan('down', ['--render' => 'errors::503'])->assertSuccessful();
        } finally {
            $this->artisan('up');
        }
    }
}
