<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser(['name' => 'Zip User', 'email' => 'zip@example.com']);
    $this->actingAs($this->user);

    // "zip codes" stored in a string column: lexicographically '80331' < '9999',
    // numerically it is not.
    foreach (['1010', '9999', '10115', '80331'] as $title) {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => $title]);
    }
});

it('compares a string column numerically for less or equal', function (): void {
    $component = Livewire::test(PostDataTable::class);
    $component->set('userFilters', [
        [['column' => 'title', 'operator' => '<=', 'value' => 9999]],
    ]);
    $component->call('loadData');

    $data = $component->instance()->getDataForTesting();

    expect(collect($data['data'])->pluck('title')->sort()->values()->toArray())
        ->toBe(['1010', '9999']);
});

it('compares a string column numerically for greater than', function (): void {
    $component = Livewire::test(PostDataTable::class);
    $component->set('userFilters', [
        [['column' => 'title', 'operator' => '>', 'value' => 9999]],
    ]);
    $component->call('loadData');

    $data = $component->instance()->getDataForTesting();

    expect(collect($data['data'])->pluck('title')->sort()->values()->toArray())
        ->toBe(['10115', '80331']);
});

it('compares a string column numerically for between', function (): void {
    $component = Livewire::test(PostDataTable::class);
    $component->set('userFilters', [
        [['column' => 'title', 'operator' => 'between', 'value' => [1000, 9999]]],
    ]);
    $component->call('loadData');

    $data = $component->instance()->getDataForTesting();

    expect(collect($data['data'])->pluck('title')->sort()->values()->toArray())
        ->toBe(['1010', '9999']);
});

it('still compares a string column as text when the value is not numeric', function (): void {
    createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Alpha']);
    createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Zulu']);

    $component = Livewire::test(PostDataTable::class);
    $component->set('userFilters', [
        [['column' => 'title', 'operator' => '>', 'value' => 'Beta']],
    ]);
    $component->call('loadData');

    $data = $component->instance()->getDataForTesting();

    expect(collect($data['data'])->pluck('title')->toArray())->toContain('Zulu')
        ->not->toContain('Alpha');
});
