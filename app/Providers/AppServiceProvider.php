<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Sola\FakeLoginServer;
use App\Services\Sola\FakeSolaServer;
use App\Services\Sola\SolaClient;
use App\Support\AbonentSession;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SolaClient::class, fn ($app): SolaClient => new SolaClient(
            $app->make(HttpFactory::class),
            (array) $app->make('config')->get('sola'),
        ));

        $this->app->scoped(AbonentSession::class);

        $this->registerTelescope();
    }

    /**
     * Telescope is a development dependency and is registered by hand, only in
     * local. Package discovery is switched off for it in composer.json, so on
     * a production deploy — where it is not even installed — nothing here
     * resolves and nothing fails.
     */
    private function registerTelescope(): void
    {
        if (! $this->app->environment('local')) {
            return;
        }

        if (! class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            return;
        }

        $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
        $this->app->register(TelescopeServiceProvider::class);
    }

    public function boot(): void
    {
        $this->installFakeBilling();

        // The templates branch on the subscriber's type in a dozen places and
        // used to reach for a static helper to get it; hand it to them instead.
        //
        // isPermanent travels with it because the templates were each deriving
        // it from the raw type, and the literal they used — type === 2 — was
        // wrong for a billing scheme that numbers legal entities and
        // individuals separately. One rule, defined once in AbonentSession.
        $this->app['view']->composer('*', function (View $view): void {
            $session = $this->app->make(AbonentSession::class);

            $view->with('abonentType', $session->abonentType());
            $view->with('isPermanent', $session->isPermanent());
        });
    }

    /**
     * Answer the billing API from memory when SOLA_FAKE is on.
     *
     * The stub is attached to the HTTP client, not to SolaClient, so the client
     * itself — signing, retries, error handling — runs exactly as it does in
     * production. The environment check is deliberate belt and braces: even
     * with SOLA_FAKE=true in a deployed .env, only a local install ever serves
     * invented balances.
     */
    private function installFakeBilling(): void
    {
        if (! $this->app->environment('local')) {
            return;
        }

        $config = $this->app->make('config');
        $baseUrl = rtrim((string) $config->get('sola.base_url'), '/');

        if ($config->get('sola.fake')) {
            $billing = new FakeSolaServer($this->app->make(CacheRepository::class), $baseUrl);
            $billing->install($this->app->make(HttpFactory::class));

            return;
        }

        if ($config->get('sola.fake_login')) {
            (new FakeLoginServer($baseUrl))->install($this->app->make(HttpFactory::class));
        }
    }
}
