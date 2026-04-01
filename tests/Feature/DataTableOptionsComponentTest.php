<?php

use Livewire\Livewire;
use TeamNiftyGmbH\DataTable\Components\DataTableOptions;

describe('DataTableOptions Component', function (): void {
    beforeEach(function (): void {
        $this->user = createTestUser();
        $this->actingAs($this->user);
    });

    describe('instantiation', function (): void {
        it('creates component with required properties', function (): void {
            $component = Livewire::test(DataTableOptions::class, [
                'availableCols' => ['name', 'email'],
                'enabledCols' => ['name'],
            ]);

            expect($component->instance())->toBeInstanceOf(DataTableOptions::class);
        });

        it('accepts empty columns', function (): void {
            $component = Livewire::test(DataTableOptions::class, [
                'availableCols' => [],
                'enabledCols' => [],
            ]);

            expect($component->instance()->enabledCols)->toBeEmpty();
        });
    });

    describe('locked properties', function (): void {
        it('has locked aggregatable property', function (): void {
            $component = Livewire::test(DataTableOptions::class, [
                'availableCols' => ['price'],
                'enabledCols' => ['price'],
                'aggregatable' => ['price'],
            ]);

            $component->assertSet('aggregatable', ['price']);
        });

        it('has locked availableCols property', function (): void {
            $component = Livewire::test(DataTableOptions::class, [
                'availableCols' => ['name', 'email'],
                'enabledCols' => ['name'],
            ]);

            $component->assertSet('availableCols', ['name', 'email']);
        });

        it('has locked isExportable property', function (): void {
            $component = Livewire::test(DataTableOptions::class, [
                'availableCols' => ['name'],
                'enabledCols' => ['name'],
                'isExportable' => true,
            ]);

            $component->assertSet('isExportable', true);
        });
    });

    describe('reorderColumns', function (): void {
        it('updates enabledCols to the new order', function (): void {
            $component = Livewire::test(DataTableOptions::class, [
                'availableCols' => ['name', 'email', 'created_at'],
                'enabledCols' => ['name', 'email', 'created_at'],
            ]);

            $component->call('reorderColumns', ['created_at', 'name', 'email']);

            $component->assertSet('enabledCols', ['created_at', 'name', 'email']);
        });

        it('dispatches options-changed with enabledCols', function (): void {
            $component = Livewire::test(DataTableOptions::class, [
                'availableCols' => ['name', 'email'],
                'enabledCols' => ['name', 'email'],
            ]);

            $component->call('reorderColumns', ['email', 'name'])
                ->assertDispatched('options-changed', fn ($name, $params) => isset($params['options']['enabledCols'])
                    && $params['options']['enabledCols'] === ['email', 'name']
                );
        });
    });

    describe('setAggregation', function (): void {
        it('adds aggregation for a column', function (): void {
            $component = Livewire::test(DataTableOptions::class, [
                'availableCols' => ['price'],
                'enabledCols' => ['price'],
                'aggregatable' => ['price'],
            ]);

            $component->call('setAggregation', 'price', 'sum');

            $component->assertSet('aggregations', ['price' => 'sum']);
        });

        it('removes aggregation when function is null', function (): void {
            $component = Livewire::test(DataTableOptions::class, [
                'availableCols' => ['price'],
                'enabledCols' => ['price'],
                'aggregatable' => ['price'],
            ]);

            $component
                ->call('setAggregation', 'price', 'sum')
                ->call('setAggregation', 'price', null);

            $component->assertSet('aggregations', []);
        });

        it('dispatches options-changed with aggregations payload', function (): void {
            $component = Livewire::test(DataTableOptions::class, [
                'availableCols' => ['price'],
                'enabledCols' => ['price'],
                'aggregatable' => ['price'],
            ]);

            $component->call('setAggregation', 'price', 'avg')
                ->assertDispatched('options-changed', fn ($name, $params) => isset($params['options']['aggregations'])
                    && $params['options']['aggregations'] === ['price' => 'avg']
                );
        });

        it('supports multiple aggregations on different columns', function (): void {
            $component = Livewire::test(DataTableOptions::class, [
                'availableCols' => ['price', 'quantity'],
                'enabledCols' => ['price', 'quantity'],
                'aggregatable' => ['price', 'quantity'],
            ]);

            $component
                ->call('setAggregation', 'price', 'sum')
                ->call('setAggregation', 'quantity', 'avg');

            $component->assertSet('aggregations', ['price' => 'sum', 'quantity' => 'avg']);
        });
    });

    describe('setGroupBy', function (): void {
        it('sets the groupBy column', function (): void {
            $component = Livewire::test(DataTableOptions::class, [
                'availableCols' => ['status', 'name'],
                'enabledCols' => ['status', 'name'],
            ]);

            $component->call('setGroupBy', 'status');

            $component->assertSet('groupBy', 'status');
        });

        it('clears groupBy when null is passed', function (): void {
            $component = Livewire::test(DataTableOptions::class, [
                'availableCols' => ['status'],
                'enabledCols' => ['status'],
            ]);

            $component
                ->call('setGroupBy', 'status')
                ->call('setGroupBy', null);

            $component->assertSet('groupBy', null);
        });

        it('dispatches options-changed with groupBy payload', function (): void {
            $component = Livewire::test(DataTableOptions::class, [
                'availableCols' => ['status'],
                'enabledCols' => ['status'],
            ]);

            $component->call('setGroupBy', 'status')
                ->assertDispatched('options-changed', fn ($name, $params) => isset($params['options']['groupBy'])
                    && $params['options']['groupBy'] === 'status'
                );
        });
    });

    describe('toggleColumn', function (): void {
        it('adds a column when not currently enabled', function (): void {
            $component = Livewire::test(DataTableOptions::class, [
                'availableCols' => ['name', 'email', 'created_at'],
                'enabledCols' => ['name', 'email'],
            ]);

            $component->call('toggleColumn', 'created_at');

            expect($component->get('enabledCols'))->toContain('created_at');
        });

        it('removes a column when currently enabled', function (): void {
            $component = Livewire::test(DataTableOptions::class, [
                'availableCols' => ['name', 'email'],
                'enabledCols' => ['name', 'email'],
            ]);

            $component->call('toggleColumn', 'email');

            expect($component->get('enabledCols'))->not->toContain('email');
        });

        it('re-indexes array after removing a column', function (): void {
            $component = Livewire::test(DataTableOptions::class, [
                'availableCols' => ['a', 'b', 'c'],
                'enabledCols' => ['a', 'b', 'c'],
            ]);

            $component->call('toggleColumn', 'b');

            $enabled = $component->get('enabledCols');
            expect(array_keys($enabled))->toBe([0, 1]);
            expect($enabled)->toBe(['a', 'c']);
        });

        it('dispatches options-changed with updated enabledCols', function (): void {
            $component = Livewire::test(DataTableOptions::class, [
                'availableCols' => ['name', 'email'],
                'enabledCols' => ['name'],
            ]);

            $component->call('toggleColumn', 'email')
                ->assertDispatched('options-changed', fn ($name, $params) => isset($params['options']['enabledCols'])
                    && in_array('email', $params['options']['enabledCols'])
                );
        });

        it('can toggle a column off and back on', function (): void {
            $component = Livewire::test(DataTableOptions::class, [
                'availableCols' => ['name', 'email'],
                'enabledCols' => ['name', 'email'],
            ]);

            $component->call('toggleColumn', 'email');
            expect($component->get('enabledCols'))->not->toContain('email');

            $component->call('toggleColumn', 'email');
            expect($component->get('enabledCols'))->toContain('email');
        });
    });
});
