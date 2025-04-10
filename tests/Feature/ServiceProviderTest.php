<?php

namespace Tests\Feature;

use Closure;
use Illuminate\Support\Facades\Blade;
use Livewire\Mechanisms\ComponentRegistry;
use PHPUnit\Framework\Attributes\Test;
use TeamNiftyGmbH\DataTable\DataTableServiceProvider;
use Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    #[Test]
    public function it_loads_views_from_package(): void
    {
        $viewFinder = $this->app['view']->getFinder();
        $packageViewsPath = __DIR__ . '/../../resources/views';

        $this->assertContains($packageViewsPath, $viewFinder->getPaths());
    }

    #[Test]
    public function it_registers_blade_directive_for_scripts(): void
    {
        $this->assertTrue(Blade::getCustomDirectives()['dataTablesScripts'] instanceof Closure);

        $output = $this->app->make('blade.compiler')->compileString('@dataTablesScripts');
        $this->assertStringContainsString('src=', $output);
        $this->assertStringContainsString('tall-datatables.assets.scripts', $output);
    }

    #[Test]
    public function it_registers_blade_directive_for_styles(): void
    {
        $this->assertTrue(Blade::getCustomDirectives()['dataTableStyles'] instanceof Closure);

        $output = $this->app->make('blade.compiler')->compileString('@dataTableStyles');
        $this->assertStringContainsString('href=', $output);
        $this->assertStringContainsString('tall-datatables.assets.styles', $output);
    }

    #[Test]
    public function it_registers_livewire_components(): void
    {
        $provider = new DataTableServiceProvider($this->app);
        $provider->boot();

        // Verify the Options component is registered
        $class = app(ComponentRegistry::class)->getClass('tall-datatables::options');
        $this->assertNotNull($class);
        $this->assertEquals(\TeamNiftyGmbH\DataTable\Livewire\Options::class, $class);
    }
}
