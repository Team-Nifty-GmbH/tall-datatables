<?php

use TeamNiftyGmbH\DataTable\DataTable;
use TeamNiftyGmbH\DataTable\Facades\DataTable as DataTableFacade;
use TeamNiftyGmbH\DataTable\Facades\DataTableDirectives;
use TeamNiftyGmbH\DataTable\Helpers\DataTableBladeDirectives;

describe('DataTable Facade', function (): void {
    it('returns the correct facade accessor', function (): void {
        $reflection = new ReflectionMethod(DataTableFacade::class, 'getFacadeAccessor');

        expect($reflection->invoke(null))->toBe(DataTable::class);
    });
});

describe('DataTableDirectives Facade', function (): void {
    it('returns the correct facade accessor', function (): void {
        $reflection = new ReflectionMethod(DataTableDirectives::class, 'getFacadeAccessor');

        expect($reflection->invoke(null))->toBe(DataTableBladeDirectives::class);
    });

    it('resolves DataTableBladeDirectives from the container', function (): void {
        $instance = app(DataTableBladeDirectives::class);

        expect($instance)->toBeInstanceOf(DataTableBladeDirectives::class);
    });
});
