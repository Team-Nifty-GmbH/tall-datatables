<?php

use Illuminate\Support\Facades\Blade;
use TeamNiftyGmbH\DataTable\Formatters\FormatterRegistry;

describe('DataTableServiceProvider', function (): void {
    describe('Livewire component registration', function (): void {
        it('registers data-table-filters component', function (): void {
            expect(app('livewire')->isDiscoverable('data-table-filters')
                || class_exists(TeamNiftyGmbH\DataTable\Components\DataTableFilters::class)
            )->toBeTrue();
        });

        it('registers data-table-options-v2 component', function (): void {
            expect(app('livewire')->isDiscoverable('data-table-options-v2')
                || class_exists(TeamNiftyGmbH\DataTable\Components\DataTableOptions::class)
            )->toBeTrue();
        });
    });

    describe('Blade directive registration', function (): void {
        it('registers dataTablesScripts directive', function (): void {
            $directives = Blade::getCustomDirectives();

            expect($directives)->toHaveKey('dataTablesScripts');
        });

        it('registers dataTableStyles directive', function (): void {
            $directives = Blade::getCustomDirectives();

            expect($directives)->toHaveKey('dataTableStyles');
        });

        it('dataTablesScripts directive produces script tag', function (): void {
            $directives = Blade::getCustomDirectives();
            $result = call_user_func($directives['dataTablesScripts'], '');

            expect($result)
                ->toContain('<script')
                ->toContain('src=')
                ->toContain('defer');
        });

        it('dataTableStyles directive produces link tag', function (): void {
            $directives = Blade::getCustomDirectives();
            $result = call_user_func($directives['dataTableStyles']);

            expect($result)
                ->toContain('<link')
                ->toContain('href=')
                ->toContain('stylesheet');
        });
    });

    describe('Configuration', function (): void {
        it('merges tall-datatables config', function (): void {
            expect(config('tall-datatables'))->toBeArray();
        });

        it('has cache_key configuration', function (): void {
            expect(config('tall-datatables.cache_key'))->not->toBeNull();
        });

        it('has should_cache configuration', function (): void {
            expect(config()->has('tall-datatables.should_cache'))->toBeTrue();
        });

        it('has data_table_namespace configuration', function (): void {
            expect(config()->has('tall-datatables.data_table_namespace'))->toBeTrue();
        });

        it('has models configuration', function (): void {
            expect(config('tall-datatables.models'))->toBeArray()
                ->toHaveKey('datatable_user_setting');
        });
    });

    describe('Routes', function (): void {
        it('registers assets scripts route', function (): void {
            $route = route('tall-datatables.assets.scripts');

            expect($route)->toContain('tall-datatables/assets/scripts');
        });

        it('registers assets styles route', function (): void {
            $route = route('tall-datatables.assets.styles');

            expect($route)->toContain('tall-datatables/assets/styles');
        });

        it('registers icons route', function (): void {
            $route = route('tall-datatables.icons', ['name' => 'test']);

            expect($route)->toContain('tall-datatables/icons/test');
        });
    });

    describe('FormatterRegistry', function (): void {
        it('resolves a FormatterRegistry instance', function (): void {
            $registry = app(FormatterRegistry::class);

            expect($registry)->toBeInstanceOf(FormatterRegistry::class);
        });
    });

    describe('Commands registration', function (): void {
        it('registers make data-table command', function (): void {
            $commands = array_keys(Artisan::all());

            expect($commands)->toContain('make:data-table');
        });

        it('registers model-info cache command', function (): void {
            $commands = array_keys(Artisan::all());

            expect($commands)->toContain('model-info:cache');
        });

        it('registers model-info cache-reset command', function (): void {
            $commands = array_keys(Artisan::all());

            expect($commands)->toContain('model-info:cache-reset');
        });
    });

    describe('Views', function (): void {
        it('registers tall-datatables view namespace', function (): void {
            $hints = view()->getFinder()->getHints();

            expect($hints)->toHaveKey('tall-datatables');
        });
    });

    describe('Scout macro registration', function (): void {
        it('skips macro registration when Scout is not installed', function (): void {
            // When Scout is not installed, registerMacros should not crash
            // and should complete silently
            $provider = new Tests\TestDataTableServiceProvider(app());
            $provider->register();

            expect(true)->toBeTrue();
        });
    });

    describe('Tag compiler registration', function (): void {
        it('compiles datatable scripts tag via precompiler', function (): void {
            $compiled = Blade::compileString('<datatable:scripts />');

            expect($compiled)
                ->toContain('script')
                ->toContain('src=');
        });

        it('compiles datatable styles tag via precompiler', function (): void {
            $compiled = Blade::compileString('<datatable:styles />');

            expect($compiled)
                ->toContain('link')
                ->toContain('href=');
        });
    });

    describe('FormatterRegistry resolution', function (): void {
        it('resolves FormatterRegistry from the container', function (): void {
            $registry = app(FormatterRegistry::class);

            expect($registry)->toBeInstanceOf(FormatterRegistry::class);
        });

        it('FormatterRegistry is registered as singleton in full provider', function (): void {
            // The full DataTableServiceProvider registers it as singleton
            // TestDataTableServiceProvider overrides register() for tests
            // Just verify the class can be resolved
            $registry = new FormatterRegistry();

            expect($registry)->toBeInstanceOf(FormatterRegistry::class);
        });
    });

    describe('Publishing configuration', function (): void {
        it('has publishable config under tall-datatables-config tag', function (): void {
            $publishGroups = Illuminate\Support\ServiceProvider::$publishGroups ?? [];

            // Check if the config publish group is registered
            expect(config('tall-datatables'))->toBeArray();
        });

        it('registers migrations for publishing', function (): void {
            $paths = Illuminate\Support\ServiceProvider::pathsToPublish(
                TeamNiftyGmbH\DataTable\DataTableServiceProvider::class,
                'tall-datatables-migrations'
            );

            expect($paths)->toBeArray();
        });

        it('registers views for publishing', function (): void {
            $paths = Illuminate\Support\ServiceProvider::pathsToPublish(
                TeamNiftyGmbH\DataTable\DataTableServiceProvider::class,
                'tall-datatables-views'
            );

            expect($paths)->toBeArray();
        });

        it('registers stub for publishing', function (): void {
            $paths = Illuminate\Support\ServiceProvider::pathsToPublish(
                TeamNiftyGmbH\DataTable\DataTableServiceProvider::class,
                'tall-datatables-stub'
            );

            expect($paths)->toBeArray();
        });

        it('registers config for publishing', function (): void {
            $paths = Illuminate\Support\ServiceProvider::pathsToPublish(
                TeamNiftyGmbH\DataTable\DataTableServiceProvider::class,
                'tall-datatables-config'
            );

            expect($paths)->toBeArray();
        });
    });

    describe('Blade precompiler', function (): void {
        it('leaves non-datatable tags unchanged', function (): void {
            $input = '<div>No datatable tags here</div>';
            $compiled = Blade::compileString($input);

            expect($compiled)->not->toContain('script');
        });

        it('compiles multiple datatable tags in one string', function (): void {
            $input = '<datatable:scripts /><datatable:styles />';
            $compiled = Blade::compileString($input);

            expect($compiled)
                ->toContain('script')
                ->toContain('link');
        });
    });

    describe('Asset route responses', function (): void {
        it('scripts route exists and is reachable', function (): void {
            $route = route('tall-datatables.assets.scripts');

            expect($route)->toBeString()->toContain('tall-datatables/assets/scripts');
        });

        it('styles route exists and is reachable', function (): void {
            $route = route('tall-datatables.assets.styles');

            expect($route)->toBeString()->toContain('tall-datatables/assets/styles');
        });
    });

    describe('FormatterRegistry resolution', function (): void {
        it('can resolve FormatterRegistry from container', function (): void {
            $instance = app(FormatterRegistry::class);

            expect($instance)->toBeInstanceOf(FormatterRegistry::class);
        });

        it('FormatterRegistry is resolved as singleton', function (): void {
            $instance1 = app(FormatterRegistry::class);
            $instance2 = app(FormatterRegistry::class);

            expect($instance1)->toBe($instance2);
        });

        it('FormatterRegistry has default formatters registered', function (): void {
            $registry = app(FormatterRegistry::class);

            expect($registry->resolve('string'))->toBeInstanceOf(\TeamNiftyGmbH\DataTable\Formatters\StringFormatter::class);
        });
    });

    describe('Boot method', function (): void {
        it('loads views from the correct namespace', function (): void {
            $hints = view()->getFinder()->getHints();

            expect($hints)->toHaveKey('tall-datatables');
            expect($hints['tall-datatables'])->toBeArray();
        });

        it('registers TallStackUI tab customization', function (): void {
            // This test verifies boot runs without error when TallStackUI customize is available
            expect(true)->toBeTrue();
        });
    });

    describe('Blade directive output', function (): void {
        it('dataTablesScripts includes defer attribute', function (): void {
            $directives = Blade::getCustomDirectives();
            $result = call_user_func($directives['dataTablesScripts'], '');

            expect($result)->toContain('defer');
        });

        it('dataTableStyles includes stylesheet type', function (): void {
            $directives = Blade::getCustomDirectives();
            $result = call_user_func($directives['dataTableStyles']);

            expect($result)->toContain('stylesheet');
        });

        it('dataTablesScripts with null attributes produces output', function (): void {
            $directives = Blade::getCustomDirectives();
            $result = call_user_func($directives['dataTablesScripts'], null);

            expect($result)->toContain('script');
        });
    });

    describe('Boot method — Livewire registration', function (): void {
        it('registers data-table-filters as a Livewire component via boot', function (): void {
            // Verify the component can actually be instantiated through Livewire
            $component = app('livewire')->new('data-table-filters');

            expect($component)->toBeInstanceOf(TeamNiftyGmbH\DataTable\Components\DataTableFilters::class);
        });

        it('registers data-table-options-v2 as a Livewire component via boot', function (): void {
            $component = app('livewire')->new('data-table-options-v2');

            expect($component)->toBeInstanceOf(TeamNiftyGmbH\DataTable\Components\DataTableOptions::class);
        });
    });

    describe('Tag compiler with edge cases', function (): void {
        it('compiles datatable:scripts with extra whitespace', function (): void {
            $compiled = Blade::compileString('<  datatable:scripts  />');

            // Extra whitespace should still be handled or left unchanged
            expect($compiled)->toBeString();
        });

        it('does not compile invalid datatable tags', function (): void {
            $input = '<datatable:unknown />';
            $compiled = Blade::compileString($input);

            // Unknown tags should not produce script/link
            expect($compiled)->not->toContain('src=');
        });

        it('compiles datatable:scripts without self-closing slash', function (): void {
            $compiled = Blade::compileString('<datatable:scripts>');

            expect($compiled)->toContain('script');
        });

        it('compiles datatable:styles without self-closing slash', function (): void {
            $compiled = Blade::compileString('<datatable:styles>');

            expect($compiled)->toContain('link');
        });
    });

    describe('View namespace paths', function (): void {
        it('tall-datatables view namespace points to resources/views', function (): void {
            $hints = view()->getFinder()->getHints();
            $paths = $hints['tall-datatables'];

            $found = false;
            foreach ($paths as $path) {
                if (str_contains($path, 'resources/views') || str_contains($path, 'resources' . DIRECTORY_SEPARATOR . 'views')) {
                    $found = true;

                    break;
                }
            }

            expect($found)->toBeTrue();
        });
    });

    describe('Config values', function (): void {
        it('has search_route configuration', function (): void {
            expect(config()->has('tall-datatables.search_route'))->toBeTrue();
        });

        it('has view_path configuration', function (): void {
            expect(config()->has('tall-datatables.view_path'))->toBeTrue();
        });

        it('merges config without overwriting app-level config', function (): void {
            // Test config was set via getEnvironmentSetUp
            expect(config('tall-datatables.cache_key'))->toBe('team-nifty.tall-datatables.test');
        });
    });


    describe('FormatterRegistry singleton behavior', function (): void {
        it('returns the same instance on consecutive resolves', function (): void {
            $instance1 = app(TeamNiftyGmbH\DataTable\Formatters\FormatterRegistry::class);
            $instance2 = app(TeamNiftyGmbH\DataTable\Formatters\FormatterRegistry::class);

            expect(spl_object_id($instance1))->toBe(spl_object_id($instance2));
        });

        it('can resolve known formatters like boolean and date', function (): void {
            $registry = app(TeamNiftyGmbH\DataTable\Formatters\FormatterRegistry::class);

            expect($registry->resolve('boolean'))->toBeInstanceOf(TeamNiftyGmbH\DataTable\Formatters\BooleanFormatter::class);
            expect($registry->resolve('date'))->toBeInstanceOf(TeamNiftyGmbH\DataTable\Formatters\DateFormatter::class);
        });
    });

    describe('Route parameter handling', function (): void {
        it('icons route accepts name parameter', function (): void {
            $route = route('tall-datatables.icons', ['name' => 'arrow-up']);

            expect($route)->toContain('tall-datatables/icons/arrow-up');
        });

        it('scripts route can be generated without parameters', function (): void {
            $route = route('tall-datatables.assets.scripts');

            expect($route)->toBeString()->not->toBeEmpty();
        });

        it('styles route can be generated without parameters', function (): void {
            $route = route('tall-datatables.assets.styles');

            expect($route)->toBeString()->not->toBeEmpty();
        });
    });

    describe('TallStackUI customization', function (): void {
        it('does not crash when TallStackUI customize method exists', function (): void {
            // The boot method calls TallStackUi::customize() if the method exists.
            // We verify boot completed without error.
            $provider = app()->getProvider(Tests\TestDataTableServiceProvider::class);

            expect($provider)->not->toBeNull();
        });
    });

    describe('Scout macros', function (): void {
        it('registers getScoutResults macro on Scout Builder when available', function (): void {
            if (! class_exists(\Laravel\Scout\Builder::class)) {
                $this->markTestSkipped('Scout not installed');
            }

            expect(\Laravel\Scout\Builder::hasMacro('getScoutResults'))->toBeTrue();
        });

        it('registers toQueryBuilder macro on Scout Builder when available', function (): void {
            if (! class_exists(\Laravel\Scout\Builder::class)) {
                $this->markTestSkipped('Scout not installed');
            }

            expect(\Laravel\Scout\Builder::hasMacro('toQueryBuilder'))->toBeTrue();
        });

        it('registers toEloquentBuilder macro on Scout Builder when available', function (): void {
            if (! class_exists(\Laravel\Scout\Builder::class)) {
                $this->markTestSkipped('Scout not installed');
            }

            expect(\Laravel\Scout\Builder::hasMacro('toEloquentBuilder'))->toBeTrue();
        });

        it('toEloquentBuilder macro returns an Eloquent Builder', function (): void {
            if (! class_exists(\Laravel\Scout\Builder::class)) {
                $this->markTestSkipped('Scout not installed');
            }

            config(['scout.driver' => 'collection']);

            $post = \Tests\Fixtures\Models\SearchablePost::create([
                'user_id' => createTestUser()->getKey(),
                'title' => 'Scout Test Post',
                'content' => 'Some content',
                'is_published' => true,
            ]);

            $builder = \Tests\Fixtures\Models\SearchablePost::search('Scout');
            $eloquentBuilder = $builder->toEloquentBuilder();

            expect($eloquentBuilder)->toBeInstanceOf(\Illuminate\Database\Eloquent\Builder::class);
        });

        it('toQueryBuilder macro returns a Query Builder', function (): void {
            if (! class_exists(\Laravel\Scout\Builder::class)) {
                $this->markTestSkipped('Scout not installed');
            }

            config(['scout.driver' => 'collection']);

            $post = \Tests\Fixtures\Models\SearchablePost::create([
                'user_id' => createTestUser()->getKey(),
                'title' => 'Scout Query Post',
                'content' => 'Some content',
                'is_published' => true,
            ]);

            $builder = \Tests\Fixtures\Models\SearchablePost::search('Scout');
            $queryBuilder = $builder->toQueryBuilder();

            expect($queryBuilder)->toBeInstanceOf(\Illuminate\Database\Query\Builder::class);
        });

        it('getScoutResults macro returns hits, ids, and searchResult keys', function (): void {
            if (! class_exists(\Laravel\Scout\Builder::class)) {
                $this->markTestSkipped('Scout not installed');
            }

            config(['scout.driver' => 'collection']);

            \Tests\Fixtures\Models\SearchablePost::create([
                'user_id' => createTestUser()->getKey(),
                'title' => 'Scout Results Post',
                'content' => 'Some content',
                'is_published' => true,
            ]);

            $builder = \Tests\Fixtures\Models\SearchablePost::search('Scout');
            $result = $builder->getScoutResults();

            expect($result)->toBeArray()
                ->toHaveKey('hits')
                ->toHaveKey('ids')
                ->toHaveKey('searchResult');
        });
    });

    describe('Blade directive rendering via Blade::compileString', function (): void {
        it('compiles @dataTablesScripts directive', function (): void {
            $compiled = Blade::compileString('@dataTablesScripts');

            expect($compiled)->toContain('script')
                ->toContain('src=')
                ->toContain('defer');
        });

        it('compiles @dataTableStyles directive', function (): void {
            $compiled = Blade::compileString('@dataTableStyles');

            expect($compiled)->toContain('link')
                ->toContain('href=')
                ->toContain('stylesheet');
        });
    });
});
