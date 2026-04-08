<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\GridPostDataTable;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('View Mode Switcher', function (): void {
    describe('availableLayouts', function (): void {
        it('defaults to table only', function (): void {
            $component = Livewire::test(PostDataTable::class);
            $layouts = $component->instance()->getAvailableLayouts();
            expect($layouts)->toBe(['table']);
        });
    });

    describe('activeLayout', function (): void {
        it('defaults to first available layout', function (): void {
            $component = Livewire::test(PostDataTable::class);
            expect($component->get('activeLayout'))->toBe('table');
        });

        it('uses getLayout override when availableLayouts is not overridden', function (): void {
            $component = Livewire::test(GridPostDataTable::class);
            $viewData = $component->instance()->getIslandData();
            expect($viewData['layout'])->toBe('tall-datatables::layouts.grid');
        });
    });

    describe('setLayout', function (): void {
        it('changes the active layout', function (): void {
            $component = Livewire::test(Tests\Fixtures\Livewire\SwitchablePostDataTable::class)
                ->call('setLayout', 'grid');
            expect($component->get('activeLayout'))->toBe('grid');
        });

        it('rejects invalid layout values', function (): void {
            $component = Livewire::test(Tests\Fixtures\Livewire\SwitchablePostDataTable::class)
                ->call('setLayout', 'kanban');
            expect($component->get('activeLayout'))->toBe('table');
        });

        it('returns correct layout view name after switching', function (): void {
            $component = Livewire::test(Tests\Fixtures\Livewire\SwitchablePostDataTable::class)
                ->call('setLayout', 'grid');
            $viewData = $component->instance()->getIslandData();
            expect($viewData['layout'])->toBe('tall-datatables::layouts.grid');
        });
    });

    describe('view data', function (): void {
        it('passes availableLayouts to view', function (): void {
            $component = Livewire::test(Tests\Fixtures\Livewire\SwitchablePostDataTable::class);
            $viewData = $component->instance()->getIslandData();
            expect($viewData['availableLayouts'])->toBe(['table', 'grid']);
        });

        it('passes activeLayout to view', function (): void {
            $component = Livewire::test(Tests\Fixtures\Livewire\SwitchablePostDataTable::class);
            $viewData = $component->instance()->getIslandData();
            expect($viewData['activeLayout'])->toBe('table');
        });
    });
});
