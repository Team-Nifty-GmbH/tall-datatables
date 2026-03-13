<?php

use Livewire\Livewire;
use TeamNiftyGmbH\DataTable\Components\DataTableOptions;

describe('DataTableOptions', function () {
    beforeEach(function (): void {
        $this->user = createTestUser();
        $this->actingAs($this->user);
    });

    it('mounts with available columns', function () {
        Livewire::test(DataTableOptions::class, [
            'availableCols' => ['name', 'email', 'created_at'],
            'enabledCols' => ['name', 'email'],
        ])
        ->assertSet('availableCols', ['name', 'email', 'created_at'])
        ->assertSet('enabledCols', ['name', 'email']);
    });

    it('toggles column visibility and dispatches event', function () {
        Livewire::test(DataTableOptions::class, [
            'availableCols' => ['name', 'email', 'created_at'],
            'enabledCols' => ['name', 'email'],
        ])
        ->call('toggleColumn', 'created_at')
        ->assertDispatched('options-changed');
    });

    it('sets aggregation and dispatches event', function () {
        Livewire::test(DataTableOptions::class, [
            'availableCols' => ['name', 'price'],
            'enabledCols' => ['name', 'price'],
            'aggregatable' => ['price'],
        ])
        ->call('setAggregation', 'price', 'sum')
        ->assertDispatched('options-changed');
    });

    it('sets group by and dispatches event', function () {
        Livewire::test(DataTableOptions::class, [
            'availableCols' => ['name', 'status'],
            'enabledCols' => ['name', 'status'],
        ])
        ->call('setGroupBy', 'status')
        ->assertDispatched('options-changed');
    });
});
