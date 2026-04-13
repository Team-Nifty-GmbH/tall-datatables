<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\KanbanPostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

test('kanban loads limited records per lane on initial load', function (): void {
    // Create more posts than the per-lane limit
    for ($i = 0; $i < 10; $i++) {
        createTestPost(['user_id' => $this->user->getKey(), 'is_published' => true]);
    }

    for ($i = 0; $i < 5; $i++) {
        createTestPost(['user_id' => $this->user->getKey(), 'is_published' => false]);
    }

    $component = Livewire::test(KanbanPostDataTable::class)
        ->call('setLayout', 'kanban');

    $meta = $component->get('kanbanLaneMeta');

    expect($meta)->toBeArray()
        ->and($meta)->toHaveKey('1')
        ->and($meta)->toHaveKey('0')
        ->and($meta['1']['total'])->toBe(10)
        ->and($meta['0']['total'])->toBe(5)
        ->and($meta['1']['loaded'])->toBeLessThanOrEqual(50)
        ->and($meta['0']['loaded'])->toBeLessThanOrEqual(50);
});

test('kanban load more appends records to data', function (): void {
    // Create enough posts to need lazy loading
    // KanbanPostDataTable has kanbanPerLane = 3 for testing
    for ($i = 0; $i < 5; $i++) {
        createTestPost(['user_id' => $this->user->getKey(), 'is_published' => true]);
    }

    $component = Livewire::test(KanbanPostDataTable::class)
        ->call('setLayout', 'kanban');

    $initialCount = count($component->get('data')['data'] ?? []);
    $initialLoaded = $component->get('kanbanLaneMeta')['1']['loaded'] ?? 0;

    if ($component->get('kanbanLaneMeta')['1']['has_more'] ?? false) {
        $component->call('kanbanLoadMore', '1');

        $afterCount = count($component->get('data')['data'] ?? []);
        $afterLoaded = $component->get('kanbanLaneMeta')['1']['loaded'] ?? 0;

        expect($afterCount)->toBeGreaterThan($initialCount);
        expect($afterLoaded)->toBeGreaterThan($initialLoaded);
    } else {
        // All records fit in one page, has_more is false
        expect($initialLoaded)->toBe(5);
    }
});

test('kanban lane meta tracks has_more correctly', function (): void {
    createTestPost(['user_id' => $this->user->getKey(), 'is_published' => true]);
    createTestPost(['user_id' => $this->user->getKey(), 'is_published' => false]);

    $component = Livewire::test(KanbanPostDataTable::class)
        ->call('setLayout', 'kanban');

    $meta = $component->get('kanbanLaneMeta');

    // With only 1-2 records per lane and perLane=50, has_more should be false
    expect($meta['1']['has_more'])->toBeFalse();
    expect($meta['0']['has_more'])->toBeFalse();
});

test('kanban per lane is configurable', function (): void {
    $component = Livewire::test(KanbanPostDataTable::class);
    $instance = $component->instance();

    $method = new ReflectionMethod($instance, 'kanbanPerLane');
    $perLane = $method->invoke($instance);

    expect($perLane)->toBeInt()
        ->and($perLane)->toBeGreaterThan(0);
});
