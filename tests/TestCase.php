<?php

namespace TeamNiftyGmbH\DataTable\Tests;

use Hammerstone\FastPaginate\FastPaginateProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use WireUi\Providers\WireUiServiceProvider;

class TestCase extends \Orchestra\Testbench\Dusk\TestCase
{
    public function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            $this->makeACleanSlate();
            $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        });

        $this->beforeApplicationDestroyed(function () {
            $this->makeACleanSlate();
        });

        parent::setUp();

    }

    public function makeACleanSlate()
    {
        Artisan::call('view:clear');

        File::deleteDirectory($this->livewireViewsPath());
        File::deleteDirectory($this->livewireClassesPath());
        File::deleteDirectory($this->livewireTestsPath());
        File::delete(app()->bootstrapPath('cache/livewire-components.php'));
    }

    protected function getPackageProviders($app)
    {
        return [
            \Livewire\LivewireServiceProvider::class,
            \TeamNiftyGmbH\DataTable\DataTableServiceProvider::class,
            WireUiServiceProvider::class,
            FastPaginateProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('view.paths', [
            __DIR__ . '/views',
            resource_path('views'),
        ]);

        $app['config']->set('app.key', 'base64:Hupx3yAySikrM2/edkZQNQHslgDWYfiBfCuSThJ5SK8=');

        // create empty sqlite file
        if (! file_exists(__DIR__ . '/database/database.sqlite')) {
            file_put_contents(__DIR__ . '/database/database.sqlite', null);
        }

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => __DIR__ . '/database/database.sqlite',
            'prefix' => '',
        ]);

        $app['config']->set('filesystems.disks.unit-downloads', [
            'driver' => 'local',
            'root' => __DIR__ . '/fixtures',
        ]);
    }

    protected function livewireClassesPath($path = '')
    {
        return app_path('Livewire' . ($path ? '/' . $path : ''));
    }

    protected function livewireViewsPath($path = '')
    {
        return resource_path('views') . '/livewire' . ($path ? '/' . $path : '');
    }

    protected function livewireTestsPath($path = '')
    {
        return base_path('tests/Feature/Livewire' . ($path ? '/' . $path : ''));
    }
}
