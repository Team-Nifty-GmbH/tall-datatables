<?php

use Livewire\Livewire;
use TeamNiftyGmbH\DataTable\Components\DataTableFilters;

describe('DataTableFilters', function (): void {
    beforeEach(function (): void {
        $this->user = createTestUser();
        $this->actingAs($this->user);
    });

    it('mounts with available columns', function (): void {
        Livewire::test(DataTableFilters::class, [
            'availableCols' => ['name', 'email', 'created_at'],
            'cacheKey' => 'test-datatable',
        ])
            ->assertSet('availableCols', ['name', 'email', 'created_at']);
    });

    it('adds a new filter', function (): void {
        Livewire::test(DataTableFilters::class, [
            'availableCols' => ['name', 'email'],
            'cacheKey' => 'test-datatable',
        ])
            ->call('addFilter', ['column' => 'name', 'operator' => 'like', 'value' => '%test%'])
            ->assertDispatched('filters-changed');
    });

    it('removes a filter', function (): void {
        Livewire::test(DataTableFilters::class, [
            'availableCols' => ['name'],
            'cacheKey' => 'test-datatable',
        ])
            ->call('addFilter', ['column' => 'name', 'operator' => 'like', 'value' => '%test%'])
            ->call('removeFilter', 0)
            ->assertDispatched('filters-changed');
    });

    it('clears all filters', function (): void {
        Livewire::test(DataTableFilters::class, [
            'availableCols' => ['name'],
            'cacheKey' => 'test-datatable',
        ])
            ->call('addFilter', ['column' => 'name', 'operator' => 'like', 'value' => '%test%'])
            ->call('clearFilters')
            ->assertSet('filters', [])
            ->assertDispatched('filters-changed');
    });
});
