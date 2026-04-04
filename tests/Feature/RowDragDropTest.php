<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;
use Tests\Fixtures\Livewire\SortablePostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('Row Drag & Drop', function (): void {
    it('renders x-sort on table body when isSortable returns true', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        Livewire::test(SortablePostDataTable::class)
            ->call('loadData')
            ->assertSeeHtml('x-sort="$wire.sortRows($item, $position)"');
    });

    it('does not render x-sort row sort handler when isSortable returns false', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        Livewire::test(PostDataTable::class)
            ->call('loadData')
            ->assertDontSeeHtml('x-sort="$wire.sortRows($item, $position)"');
    });

    it('renders x-sort:item on table rows when sortable', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey()]);

        Livewire::test(SortablePostDataTable::class)
            ->call('loadData')
            ->assertSeeHtml('x-sort:item="' . $post->getKey() . '"');
    });

    it('does not render x-sort:item on table rows when not sortable', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey()]);

        Livewire::test(PostDataTable::class)
            ->call('loadData')
            ->assertDontSeeHtml('x-sort:item="' . $post->getKey() . '"');
    });

    it('calls sortRows when row is reordered', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(SortablePostDataTable::class)
            ->call('loadData')
            ->call('sortRows', $post->getKey(), 3);

        expect($component->get('sortedRows'))->toHaveCount(1)
            ->and($component->get('sortedRows')[0])
            ->toBe(['id' => $post->getKey(), 'position' => 3]);
    });

    it('exposes isSortable in view data', function (): void {
        $component = Livewire::test(SortablePostDataTable::class);

        $reflection = new ReflectionMethod($component->instance(), 'isSortable');
        expect($reflection->invoke($component->instance()))->toBeTrue();
    });
});
