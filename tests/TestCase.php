<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use TallStackUi\TallStackUiServiceProvider;
use TeamNiftyGmbH\DataTable\DataTableServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register TallStackUi facade alias for views
        $this->app->bind('TallStackUi', \TallStackUi\TallStackUi::class);
        if (! class_exists('TallStackUi', false)) {
            class_alias(\TallStackUi\Facades\TallStackUi::class, 'TallStackUi');
        }
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            TallStackUiServiceProvider::class,
            TestDataTableServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('tall-datatables.cache_key', 'team-nifty.tall-datatables.test');
        $app['config']->set('tall-datatables.should_cache', false);

        $app['config']->set('cache.default', 'array');
        $app['config']->set('cache.stores.array', [
            'driver' => 'array',
            'serialize' => false,
        ]);

        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}

/**
 * Test Service Provider that doesn't load package migrations
 * to avoid conflicts with test migrations
 */
class TestDataTableServiceProvider extends DataTableServiceProvider
{
    public function register(): void
    {
        $this->registerBladeDirectives();
        $this->registerTagCompiler();
        $this->registerMacros();

        // Don't load package migrations - test migrations handle this
        // $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->mergeConfigFrom(
            __DIR__ . '/../config/tall-datatables.php',
            'tall-datatables'
        );
    }
}
