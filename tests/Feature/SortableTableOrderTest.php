<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\OrderedSortablePostDataTable;
use Tests\Fixtures\Livewire\PostDataTable;
use Tests\Fixtures\Livewire\SortablePostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);

    foreach (['B', 'C', 'A'] as $title) {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => $title]);
    }
});

function shownTitles($component): array
{
    $component->instance()->loadData();

    return array_column($component->instance()->data['data'], 'title');
}

it('shows a sortable table in the order of its builder', function (): void {
    $component = Livewire::test(OrderedSortablePostDataTable::class);

    expect(shownTitles($component))->toBe(['A', 'B', 'C']);
});

it('lets a column the user sorts by win over the order of the builder', function (): void {
    $component = Livewire::test(OrderedSortablePostDataTable::class)
        ->call('sortTable', 'title')
        ->call('sortTable', 'title');

    expect(shownTitles($component))->toBe(['C', 'B', 'A']);
});

it('keeps the newest first default for a table that is not sortable', function (): void {
    $component = Livewire::test(PostDataTable::class);

    expect(shownTitles($component))->toBe(['A', 'C', 'B']);
});

it('falls back to newest first for a sortable table whose builder does not sort', function (): void {
    $component = Livewire::test(SortablePostDataTable::class);

    expect(shownTitles($component))->toBe(['A', 'C', 'B']);
});
