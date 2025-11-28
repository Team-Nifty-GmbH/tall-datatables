<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use TallStackUi\TallStackUiServiceProvider;
use TeamNiftyGmbH\DataTable\DataTableServiceProvider;
use Tests\Fixtures\Livewire\PostDataTable;
use Tests\Fixtures\Livewire\PostWithRelationsDataTable;
use Tests\Fixtures\Livewire\SelectablePostDataTable;
use Tests\Fixtures\Livewire\UserDataTable;

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

        // Register test Livewire components for browser tests
        Livewire::component('post-data-table', PostDataTable::class);
        Livewire::component('user-data-table', UserDataTable::class);
        Livewire::component('selectable-post-data-table', SelectablePostDataTable::class);
        Livewire::component('post-with-relations-data-table', PostWithRelationsDataTable::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    protected function defineRoutes($router): void
    {
        // Register test routes for browser tests - these provide static routes
        // for basic rendering tests before dynamic routes are needed
        $router->get('/test-datatable', PostDataTable::class);
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

        // Add test views path for browser test layouts
        $app['config']->set('view.paths', array_merge(
            $app['config']->get('view.paths', []),
            [__DIR__ . '/resources/views']
        ));
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            TallStackUiServiceProvider::class,
            TestDataTableServiceProvider::class,
        ];
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
