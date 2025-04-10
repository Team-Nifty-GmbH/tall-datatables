<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\ComponentAttributeBag;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use TallStackUi\Facades\TallStackUi;
use TallStackUi\TallStackUiServiceProvider;
use TeamNiftyGmbH\DataTable\DataTableServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Tall-datatables config
        $app['config']->set('tall-datatables.should_cache', false);
        $app['config']->set('tall-datatables.models.datatable_user_setting', \TeamNiftyGmbH\DataTable\Models\DatatableUserSetting::class);

        // Package testing config
        $app['config']->set('tall-datatables.data_table_namespace', 'Tests\\Datatables');
        $app['config']->set('tall-datatables.view_path', __DIR__ . '/views');

        // Livewire config
        $app['config']->set('livewire.class_namespace', 'Tests');
    }

    protected function getPackageAliases($app): array
    {
        return [
            'TallStackUi' => TallStackUi::class,
        ];
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            TallStackUiServiceProvider::class,
            DataTableServiceProvider::class,
        ];
    }

    protected function resolveComponentAttributeBag(array $attributes = []): ComponentAttributeBag
    {
        return new ComponentAttributeBag($attributes);
    }
}
