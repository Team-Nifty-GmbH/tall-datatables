<?php

use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\RowClickPostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
    $this->post = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Clicked Row Post']);
});

function dispatchFromBodyIsland(Testable $component, string $event, array $record): Testable
{
    // Livewire 4.4.4 tags a window event listener call with the island the event was dispatched from
    return $component->update(calls: [
        [
            'method' => '__dispatch',
            'params' => [$event, ['record' => $record]],
            'path' => '',
            'metadata' => ['island' => ['name' => 'body', 'mode' => 'morph']],
        ],
    ]);
}

function bodyIslandFragment(Testable $component): ?string
{
    return collect(data_get($component->effects, 'islandFragments', []))
        ->first(fn (string $fragment): bool => str_contains($fragment, 'name=body'));
}

it('does not send an empty body island when a renderless row click listener skips loadData', function (): void {
    $component = Livewire::test(RowClickPostDataTable::class)->call('loadData');

    dispatchFromBodyIsland($component, 'data-table-row-clicked', ['id' => $this->post->getKey()]);

    expect($component->get('clickedId'))->toBe($this->post->getKey())
        ->and(bodyIslandFragment($component) ?? '')->not->toContain(__('No data found'));
});

it('keeps the full render of a row click listener that forces one', function (): void {
    $component = Livewire::test(RowClickPostDataTable::class)->call('loadData');

    dispatchFromBodyIsland($component, 'data-table-row-edit', ['id' => $this->post->getKey()]);

    expect($component->get('clickedId'))->toBe($this->post->getKey())
        ->and(data_get($component->effects, 'html'))->toContain('Clicked Row Post');
});
