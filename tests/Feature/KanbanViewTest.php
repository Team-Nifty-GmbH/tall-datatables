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
        it('returns kanban layout view name', function (): void {
            $component = Livewire::test(KanbanPostDataTable::class)
                ->call('setLayout', 'kanban');

            $viewData = $component->instance()->getIslandData();
            expect($viewData['layout'])->toBe('tall-datatables::layouts.kanban');
        });
    });
});
