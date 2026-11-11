<?php

/**
 * The filter row is cloned by Alpine from a template, so a full render that
 * adds or drops the score head cell does not reach the cells already cloned.
 * Every filter input then sits one column off its heading.
 */

use Tests\Fixtures\Livewire\RankingScoreSearchableDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Score User', 'email' => 'score@example.com']);
    createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Semantic Match Post']);
});

function headAndFilterCellCounts($page): array
{
    $counts = $page->script('() => {
        const rows = document.querySelectorAll("thead tr");
        const cells = (row) => row ? row.querySelectorAll(":scope > th, :scope > td").length : 0;
        return [cells(rows[0]), cells(rows[1])];
    }');

    return is_array($counts[0] ?? null) ? $counts[0] : $counts;
}

function waitForRelevance($page, bool $expected): void
{
    $page->script('() => new Promise((resolve) => {
        const start = Date.now();
        const check = () => {
            const wireId = document.querySelector("[wire\\\\:id]")?.getAttribute("wire:id");
            const has = window.Livewire?.find(wireId)?.$get("hasRelevance");
            if (has === ' . ($expected ? 'true' : 'false') . ' || Date.now() - start > 15000) return setTimeout(resolve, 500);
            setTimeout(check, 200);
        };
        check();
    })');
}

it('keeps the filter row aligned with the head when the score column comes and goes', function (): void {
    $page = visitLivewire(RankingScoreSearchableDataTable::class);
    $page->wait(3);

    [$head, $filter] = headAndFilterCellCounts($page);
    expect($filter)->toBe($head);

    $page->script('() => {
        const wireId = document.querySelector("[wire\\\\:id]")?.getAttribute("wire:id");
        window.Livewire.find(wireId).$set("search", "Semantic");
    }');
    waitForRelevance($page, true);

    [$head, $filter] = headAndFilterCellCounts($page);
    expect($filter)->toBe($head, 'filter row must gain the score cell together with the head');

    $page->script('() => {
        const wireId = document.querySelector("[wire\\\\:id]")?.getAttribute("wire:id");
        window.Livewire.find(wireId).$set("search", "Nothing matches this");
    }');
    waitForRelevance($page, false);

    [$head, $filter] = headAndFilterCellCounts($page);
    expect($filter)->toBe($head, 'filter row must lose the score cell together with the head');
});
