<?php

/**
 * Dropping a row must report the position among the rows.
 *
 * The body of the table also holds the loading overlay row in front of the records. Sortable
 * counted that row as long as it was not told which children it may move, so every position
 * handed to sortRows() was one too high.
 */

use Tests\Fixtures\Livewire\SortablePostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Test User', 'email' => 'sortable@example.com']);

    for ($i = 1; $i <= 4; $i++) {
        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => "Sortable Post {$i}",
            'content' => "Content {$i}",
            'is_published' => true,
        ]);
    }
});

it('reports the position among the rows when a row is dropped', function (): void {
    $page = visitLivewire(SortablePostDataTable::class);

    $page->wait(2);

    // Rows show newest first: ids 4, 3, 2, 1. Dropping id 4 onto id 2 puts it third, index 2.
    $page->drag('tbody tr[x-sort\\:item="4"] td:last-child', 'tbody tr[x-sort\\:item="2"] td:last-child');

    $page->wait(1);

    $sortedRows = $page->script('() => window.Livewire.find(
        document.querySelector("[wire\\\\:id]").getAttribute("wire:id")
    ).$get("sortedRows")');

    expect($sortedRows)->toBe([['id' => 4, 'position' => 2]]);
});
