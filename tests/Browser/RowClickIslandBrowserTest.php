<?php

/**
 * A row click must not empty the table.
 *
 * The row sits inside the body island, so Livewire tags the listener call of
 * the dispatched data-table-row-clicked event with that island and renders it
 * again afterwards. The rows are only loaded by loadData(), so a listener that
 * does not call it used to get an empty body back and the table showed
 * "No data found" until the page was reloaded.
 */

use Tests\Fixtures\Livewire\RowClickPostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Test User', 'email' => 'row-click@example.com']);

    for ($i = 1; $i <= 5; $i++) {
        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => "Row Click Post {$i}",
            'content' => "Content {$i}",
            'is_published' => true,
        ]);
    }
});

it('keeps the rows after a row click whose listener does not load data', function (): void {
    $page = visitLivewire(RowClickPostDataTable::class);

    $page->wait(2);

    $result = $page->script('() => {
        return new Promise((resolve) => {
            const rows = () => document.querySelectorAll("tbody tr[wire\\\\:key^=\\"row-\\"]");
            const wireId = document.querySelector("[wire\\\\:id]").getAttribute("wire:id");
            const start = Date.now();

            const click = () => {
                const row = rows()[2];
                if (! row) {
                    if (Date.now() - start > 10000) return resolve({ error: "no row" });

                    return setTimeout(click, 100);
                }

                row.querySelector("td:last-child").click();
                check();
            };

            const check = () => {
                const clickedId = window.Livewire.find(wireId).$get("clickedId");
                if (clickedId === null && Date.now() - start < 10000) return setTimeout(check, 100);

                setTimeout(() => resolve({ clickedId, rows: rows().length }), 500);
            };

            click();
        });
    }');

    $data = is_array($result) && isset($result[0]) && is_array($result[0]) ? $result[0] : $result;

    expect($data['clickedId'] ?? null)->not->toBeNull()
        ->and($data['rows'] ?? 0)->toBe(5);
});
