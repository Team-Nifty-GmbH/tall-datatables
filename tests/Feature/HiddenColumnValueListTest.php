<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->actingAs(createTestUser());
});

it('offers the value list of a boolean column that is not shown', function (): void {
    $component = Livewire::test(PostDataTable::class, ['enabledCols' => ['title']])
        ->call('loadData');

    $lists = $component->get('filterValueLists');

    expect($component->get('enabledCols'))->not->toContain('is_published')
        ->and($lists)->toHaveKey('is_published')
        ->and(array_column($lists['is_published'], 'value'))->toBe([1, 0]);
});

it('does not read an unknown operator as the value to compare with', function (): void {
    createTestPost(['user_id' => auth()->id(), 'title' => 'Published', 'is_published' => true]);
    createTestPost(['user_id' => auth()->id(), 'title' => 'Draft', 'is_published' => false]);

    $component = Livewire::test(PostDataTable::class);
    $component->set('userFilters', [
        [['column' => 'is_published', 'operator' => 'true', 'value' => 'ja']],
    ]);
    $component->call('loadData');

    expect($component->instance()->getDataForTesting()['total'])->toBe(2);
});
