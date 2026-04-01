<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser(['name' => 'Agg User', 'email' => 'agg@test.com']);
    $this->actingAs($this->user);
});

describe('SupportsAggregation', function (): void {
    describe('applyAggregations', function (): void {
        it('loads data after applying aggregations', function (): void {
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'A', 'price' => 100]);
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'B', 'price' => 200]);

            $component = Livewire::test(PostDataTable::class);
            $component->set('aggregatableCols', ['sum' => ['price']]);
            $component->call('applyAggregations');

            $data = $component->instance()->getDataForTesting();
            expect($data['total'])->toBe(2);
        });

        it('adds missing columns to enabledCols when aggregating', function (): void {
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'A', 'price' => 100]);

            $component = Livewire::test(PostDataTable::class);

            // Remove price from enabledCols
            $enabledCols = $component->get('enabledCols');
            $enabledCols = array_values(array_diff($enabledCols, ['price']));
            $component->set('enabledCols', $enabledCols);

            // Now aggregate on price which is not in enabledCols
            $component->set('aggregatableCols', ['sum' => ['price']]);
            $component->call('applyAggregations');

            $newEnabledCols = $component->get('enabledCols');
            expect($newEnabledCols)->toContain('price');
        });
    });

    describe('getAggregatable', function (): void {
        it('returns an array of numeric column names when aggregatable is wildcard', function (): void {
            $component = Livewire::test(PostDataTable::class);

            $reflection = new ReflectionMethod($component->instance(), 'getAggregatable');
            $result = $reflection->invoke($component->instance());

            expect($result)->toBeArray();
        });

        it('excludes foreign key columns from aggregation', function (): void {
            $component = Livewire::test(PostDataTable::class);

            $reflection = new ReflectionMethod($component->instance(), 'getAggregatable');
            $result = $reflection->invoke($component->instance());

            expect($result)->not->toContain('user_id')
                ->and($result)->not->toContain('id');
        });

        it('returns custom aggregatable columns when not wildcard', function (): void {
            $component = Livewire::test(PostDataTable::class);
            $component->set('aggregatable', ['price', 'quantity']);

            $reflection = new ReflectionMethod($component->instance(), 'getAggregatable');
            $result = $reflection->invoke($component->instance());

            expect($result)->toBe(['price', 'quantity']);
        });
    });

    describe('getAggregatableRelationCols', function (): void {
        it('returns an empty array by default', function (): void {
            $component = Livewire::test(PostDataTable::class);

            $reflection = new ReflectionMethod($component->instance(), 'getAggregatableRelationCols');
            $result = $reflection->invoke($component->instance());

            expect($result)->toBe([]);
        });
    });

    describe('getAggregate', function (): void {
        it('calculates sum aggregate', function (): void {
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'A', 'price' => 100]);
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'B', 'price' => 200]);

            $component = Livewire::test(PostDataTable::class);
            $component->set('aggregatableCols', ['sum' => ['price']]);
            $component->call('loadData');

            $data = $component->instance()->getDataForTesting();
            expect($data)->toHaveKey('aggregates');
        });

        it('calculates avg aggregate', function (): void {
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'A', 'price' => 100]);
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'B', 'price' => 200]);

            $component = Livewire::test(PostDataTable::class);
            $component->set('aggregatableCols', ['avg' => ['price']]);
            $component->call('loadData');

            $data = $component->instance()->getDataForTesting();
            expect($data)->toHaveKey('aggregates');
        });

        it('calculates min aggregate', function (): void {
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'A', 'price' => 100]);
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'B', 'price' => 200]);

            $component = Livewire::test(PostDataTable::class);
            $component->set('aggregatableCols', ['min' => ['price']]);
            $component->call('loadData');

            $data = $component->instance()->getDataForTesting();
            expect($data)->toHaveKey('aggregates');
        });

        it('calculates max aggregate', function (): void {
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'A', 'price' => 100]);
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'B', 'price' => 200]);

            $component = Livewire::test(PostDataTable::class);
            $component->set('aggregatableCols', ['max' => ['price']]);
            $component->call('loadData');

            $data = $component->instance()->getDataForTesting();
            expect($data)->toHaveKey('aggregates');
        });

        it('skips invalid aggregate types', function (): void {
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'A', 'price' => 100]);

            $component = Livewire::test(PostDataTable::class);
            $component->set('aggregatableCols', ['invalid_type' => ['price']]);
            $component->call('loadData');

            $data = $component->instance()->getDataForTesting();
            // Should not crash and should not include invalid_type in aggregates
            expect($data)->toBeArray();
        });

        it('skips columns not in enabledCols for aggregation', function (): void {
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'A', 'price' => 100]);

            $component = Livewire::test(PostDataTable::class);
            // Set enabledCols without price, and try to aggregate on price
            $component->set('enabledCols', ['title', 'content']);
            $component->set('aggregatableCols', ['sum' => ['price']]);
            $component->call('loadData');

            $data = $component->instance()->getDataForTesting();
            expect($data)->toBeArray();
        });

        it('handles non-array column value in aggregatableCols', function (): void {
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'A', 'price' => 100]);

            $component = Livewire::test(PostDataTable::class);
            $component->set('aggregatableCols', ['sum' => 'price']);
            $component->call('loadData');

            $data = $component->instance()->getDataForTesting();
            expect($data)->toBeArray();
        });

        it('calculates multiple aggregate types at once', function (): void {
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'A', 'price' => 100]);
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'B', 'price' => 200]);
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'C', 'price' => 300]);

            $component = Livewire::test(PostDataTable::class);
            $component->set('aggregatableCols', [
                'sum' => ['price'],
                'avg' => ['price'],
                'min' => ['price'],
                'max' => ['price'],
            ]);
            $component->call('loadData');

            $data = $component->instance()->getDataForTesting();
            expect($data)->toHaveKey('aggregates');
        });
    });
});
