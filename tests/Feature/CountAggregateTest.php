<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('Count Aggregate', function (): void {
    it('has count in aggregatableCols structure', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $cols = $component->get('aggregatableCols');
        expect($cols)->toHaveKey('count');
    });

    it('computes count aggregate for a column', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'A', 'price' => 100]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'B', 'price' => 200]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'C', 'price' => null]);

        $component = Livewire::test(PostDataTable::class);
        $component->set('aggregatableCols', ['count' => ['price']]);
        $component->call('applyAggregations');

        $data = $component->instance()->getDataForTesting();
        expect($data)->toHaveKey('aggregates')
            ->and($data['aggregates']['count']['price']['raw'] ?? $data['aggregates']['count']['price'])->toBe(2); // null not counted
    });

    it('count aggregate counts all rows when applied to non-nullable column', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'A']);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'B']);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'C']);

        $component = Livewire::test(PostDataTable::class);
        $component->set('aggregatableCols', ['count' => ['title']]);
        $component->call('applyAggregations');

        $data = $component->instance()->getDataForTesting();
        expect($data['aggregates']['count']['title']['raw'] ?? $data['aggregates']['count']['title'])->toBe(3);
    });

    it('count works alongside other aggregates', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'A', 'price' => 100]);
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'B', 'price' => 200]);

        $component = Livewire::test(PostDataTable::class);
        $component->set('aggregatableCols', [
            'sum' => ['price'],
            'count' => ['price'],
        ]);
        $component->call('applyAggregations');

        $data = $component->instance()->getDataForTesting();
        $sumRaw = $data['aggregates']['sum']['price']['raw'] ?? $data['aggregates']['sum']['price'];
        $countRaw = $data['aggregates']['count']['price']['raw'] ?? $data['aggregates']['count']['price'];

        expect((float) $sumRaw)->toBe(300.0)
            ->and($countRaw)->toBe(2);
    });

    it('count is included in saved filter settings', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('loadData')
            ->set('aggregatableCols', ['count' => ['price']])
            ->call('saveFilter', 'Count Filter');

        $savedFilters = $component->get('savedFilters');
        $settings = data_get($savedFilters, '0.settings');

        expect($settings['aggregatableCols'])->toHaveKey('count');
    });

    it('count label is available in group labels', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $reflection = new ReflectionMethod($component->instance(), 'getGroupLabels');
        $labels = $reflection->invoke($component->instance());

        expect($labels)->toHaveKey('count');
    });

    it('count label is available in operator labels', function (): void {
        $component = Livewire::test(PostDataTable::class);

        $labels = $component->instance()->getOperatorLabels();

        expect($labels)->toHaveKey('count');
    });
});
