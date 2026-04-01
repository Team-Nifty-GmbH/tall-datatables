<?php

use Illuminate\Support\Facades\Blade;
use TeamNiftyGmbH\DataTable\Formatters\FormatterRegistry;

describe('DataTableServiceProvider', function (): void {
    describe('Livewire component registration', function (): void {
        it('registers data-table-filters component', function (): void {
            expect(app('livewire')->isDiscoverable('data-table-filters')
                || class_exists(\TeamNiftyGmbH\DataTable\Components\DataTableFilters::class)
            )->toBeTrue();
        });

        it('registers data-table-options-v2 component', function (): void {
            expect(app('livewire')->isDiscoverable('data-table-options-v2')
                || class_exists(\TeamNiftyGmbH\DataTable\Components\DataTableOptions::class)
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
            $commands = array_keys(\Artisan::all());

            expect($commands)->toContain('make:data-table');
        });

        it('registers model-info cache command', function (): void {
            $commands = array_keys(\Artisan::all());

            expect($commands)->toContain('model-info:cache');
        });

        it('registers model-info cache-reset command', function (): void {
            $commands = array_keys(\Artisan::all());

            expect($commands)->toContain('model-info:cache-reset');
        });
    });

    describe('Views', function (): void {
        it('registers tall-datatables view namespace', function (): void {
            $hints = view()->getFinder()->getHints();

            expect($hints)->toHaveKey('tall-datatables');
        });
    });
});
