<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('Resizable columns', function (): void {
    test('isResizable is always true', function (): void {
        $component = Livewire::test(PostDataTable::class);
        $viewData = $component->instance()->getIslandData();

        expect($viewData['isResizable'])->toBeTrue();
    });

    test('colWidths defaults to empty array', function (): void {
        $component = Livewire::test(PostDataTable::class);

        expect($component->get('colWidths'))->toBe([]);
    });

    test('storeColWidths persists column widths', function (): void {
        $widths = ['title' => 200, 'content' => 300];

        $component = Livewire::test(PostDataTable::class)
            ->call('storeColWidths', $widths);

        expect($component->get('colWidths'))->toBe($widths);
    });

    test('resetLayout clears colWidths', function (): void {
        $component = Livewire::test(PostDataTable::class)
            ->call('storeColWidths', ['title' => 200])
            ->call('resetLayout');

        expect($component->get('colWidths'))->toBe([]);
    });
});
