<?php

/**
 * A data table rendered inside another one must keep its own rows.
 *
 * Both tables render their rows from the same Blade view, so their body islands
 * carry the same token. Livewire looks up the island to morph by that token and
 * does not stop at nested components, so re-rendering the outer table used to
 * put its rows into the inner one.
 */

use Tests\Fixtures\Livewire\NestedTablePostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $user = createTestUser(['name' => 'Nested User', 'email' => 'nested@example.com']);

    for ($i = 1; $i <= 3; $i++) {
        createTestPost([
            'user_id' => $user->getKey(),
            'title' => "Outer Post {$i}",
            'content' => "Content {$i}",
            'is_published' => true,
        ]);
    }
});

it('morphs the rows of the outer table into its own body', function (): void {
    $page = visitLivewire(NestedTablePostDataTable::class);

    $page->wait(2);

    $result = $page->script('() => {
        return new Promise((resolve) => {
            const nested = () => document.querySelector("#nested-table");
            const outerId = document.querySelector("[wire\\\\:id]").getAttribute("wire:id");
            const start = Date.now();

            const texts = (rows) => Array.from(rows).map((row) => row.textContent.replace(/\s+/g, " ").trim());
            const outerRows = () => Array.from(document.querySelectorAll("tbody tr[wire\\\\:key^=\\"row-\\"]"))
                .filter((row) => ! nested().contains(row));
            const innerRows = () => nested().querySelectorAll("tbody tr[wire\\\\:key^=\\"row-\\"]");

            const run = () => {
                if (outerRows().length < 3 || innerRows().length < 1) {
                    if (Date.now() - start > 10000) return resolve({ error: "tables not loaded" });

                    return setTimeout(run, 100);
                }

                window.Livewire.find(outerId).$call("sortTable", "title").then(() => {
                    setTimeout(() => resolve({
                        outer: texts(outerRows()),
                        inner: texts(innerRows()),
                    }), 500);
                });
            };

            run();
        });
    }');

    $data = is_array($result) && isset($result[0]) && is_array($result[0]) ? $result[0] : $result;

    expect($data['error'] ?? null)->toBeNull()
        ->and(implode(' ', $data['inner'] ?? []))->not->toContain('Outer Post')
        ->and(implode(' ', $data['inner'] ?? []))->toContain('Nested User')
        ->and(count($data['outer'] ?? []))->toBe(3);
});
