<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\KanbanPostDataTable;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('Kanban View', function (): void {
    describe('configuration', function (): void {
        it('provides kanbanColumn', function (): void {
            $component = Livewire::test(KanbanPostDataTable::class);
            $viewData = $component->instance()->getIslandData();
            expect($viewData['kanbanColumn'])->toBe('is_published');
        });

        it('provides kanbanLanes when configured', function (): void {
            $component = Livewire::test(KanbanPostDataTable::class);
            $viewData = $component->instance()->getIslandData();
            expect($viewData['kanbanLanes'])->toBeArray()
                ->and($viewData['kanbanLanes']['1']['label'])->toBe('Published')
                ->and($viewData['kanbanLanes']['0']['color'])->toBe('gray');
        });

        it('returns null for kanbanColumn on non-kanban datatables', function (): void {
            $component = Livewire::test(PostDataTable::class);
            $viewData = $component->instance()->getIslandData();
            expect($viewData['kanbanColumn'])->toBeNull();
        });

        it('returns null for kanbanCardView by default', function (): void {
            $component = Livewire::test(KanbanPostDataTable::class);
            $viewData = $component->instance()->getIslandData();
            expect($viewData['kanbanCardView'])->toBeNull();
        });
    });

    describe('kanbanMoveItem', function (): void {
        it('updates the record via kanbanMoveItem', function (): void {
            $post = createTestPost(['user_id' => $this->user->getKey(), 'is_published' => false]);

            Livewire::test(KanbanPostDataTable::class)
                ->call('kanbanMoveItem', $post->getKey(), '1');

            expect($post->fresh()->is_published)->toBeTrue();
        });

        it('throws on base DataTable when not overridden', function (): void {
            $component = Livewire::test(PostDataTable::class);

            expect(fn () => $component->instance()->kanbanMoveItem(1, 'test'))
                ->toThrow(BadMethodCallException::class);
        });
    });

    describe('layout selection', function (): void {
        test('returns kanban layout view name', function (): void {
            $component = Livewire::test(KanbanPostDataTable::class)
                ->call('setLayout', 'kanban');

            $viewData = $component->instance()->getIslandData();
            expect($viewData['layout'])->toBe('tall-datatables::layouts.kanban');
        });

        test('switches from table to kanban and back', function (): void {
            createTestPost(['user_id' => $this->user->getKey(), 'is_published' => true]);

            $component = Livewire::test(KanbanPostDataTable::class);

            expect($component->instance()->activeLayout)->toBe('table');

            $component->call('setLayout', 'kanban');
            expect($component->instance()->activeLayout)->toBe('kanban');

            $component->call('setLayout', 'table');
            expect($component->instance()->activeLayout)->toBe('table');
        });
    });

    describe('kanban data loading', function (): void {
        test('loads data split by lanes', function (): void {
            createTestPost(['user_id' => $this->user->getKey(), 'is_published' => true, 'title' => 'Published']);
            createTestPost(['user_id' => $this->user->getKey(), 'is_published' => false, 'title' => 'Draft']);

            $component = Livewire::test(KanbanPostDataTable::class)
                ->call('setLayout', 'kanban');

            $data = $component->instance()->getDataForTesting();
            expect($data['data'])->toHaveCount(2);

            $laneMeta = $component->instance()->kanbanLaneMeta;
            expect($laneMeta)->toHaveKeys(['1', '0'])
                ->and($laneMeta['1']['loaded'])->toBe(1)
                ->and($laneMeta['0']['loaded'])->toBe(1);
        });

        test('tracks lane totals and has_more correctly', function (): void {
            for ($i = 0; $i < 3; $i++) {
                createTestPost(['user_id' => $this->user->getKey(), 'is_published' => true]);
            }

            createTestPost(['user_id' => $this->user->getKey(), 'is_published' => false]);

            $component = Livewire::test(KanbanPostDataTable::class)
                ->call('setLayout', 'kanban');

            $laneMeta = $component->instance()->kanbanLaneMeta;
            expect($laneMeta['1']['total'])->toBe(3)
                ->and($laneMeta['0']['total'])->toBe(1);
        });

        test('kanbanLoadMore increases loaded count', function (): void {
            for ($i = 0; $i < 60; $i++) {
                createTestPost(['user_id' => $this->user->getKey(), 'is_published' => true]);
            }

            $component = Livewire::test(KanbanPostDataTable::class)
                ->call('setLayout', 'kanban');

            $initialLoaded = $component->instance()->kanbanLaneMeta['1']['loaded'];

            $component->call('kanbanLoadMore', '1');

            $newLoaded = $component->instance()->kanbanLaneMeta['1']['loaded'];
            expect($newLoaded)->toBeGreaterThan($initialLoaded);
        });

        test('search resets kanbanLaneMeta', function (): void {
            createTestPost(['user_id' => $this->user->getKey(), 'is_published' => true, 'title' => 'Findable']);
            createTestPost(['user_id' => $this->user->getKey(), 'is_published' => false, 'title' => 'Other']);

            $component = Livewire::test(KanbanPostDataTable::class)
                ->call('setLayout', 'kanban');

            expect($component->instance()->kanbanLaneMeta)->not->toBeEmpty();

            $component->set('search', 'Findable');

            $laneMeta = $component->instance()->kanbanLaneMeta;
            expect($laneMeta['1']['total'])->toBeLessThanOrEqual(1);
        });
    });
});
