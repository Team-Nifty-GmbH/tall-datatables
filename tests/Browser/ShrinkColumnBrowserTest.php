<?php

/**
 * A column has to get narrower, not only wider.
 *
 * The fixed layout only decides a column's width once the table itself has a
 * definite one. While the table stays at `width: auto` the browser keeps
 * sizing on content, a width set on the cell reads as a wish and the content
 * overrules it from below. A column then stops at whatever its widest cell
 * needs and dragging to the left does nothing at all, which is the one
 * direction that matters on a table already too wide for the screen.
 */

use Tests\Fixtures\Livewire\ResizablePostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Test User', 'email' => 'shrink@example.com']);

    for ($i = 1; $i <= 5; $i++) {
        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => "A Rather Long Post Title {$i}",
            'content' => "Content {$i}",
            'is_published' => true,
        ]);
    }
});

it('drags a column below the width of its own content', function (): void {
    $page = visitLivewire(ResizablePostDataTable::class);

    $page->wait(2);

    $result = $page->script('() => {
        return new Promise((resolve) => {
            const handle = document.querySelectorAll(".cursor-col-resize")[0];
            if (! handle) return resolve({ error: "no handle" });

            const th = handle.closest("th");
            const rect = handle.getBoundingClientRect();
            const x = rect.left + rect.width / 2;
            const y = rect.top + rect.height / 2;
            const natural = th.offsetWidth;
            const distance = 60 - natural;

            handle.dispatchEvent(new MouseEvent("mousedown", { clientX: x, clientY: y, bubbles: true }));
            document.dispatchEvent(new MouseEvent("mousemove", { clientX: x + distance, clientY: y, bubbles: true }));
            document.dispatchEvent(new MouseEvent("mouseup", { clientX: x + distance, clientY: y, bubbles: true }));

            setTimeout(() => resolve({
                natural,
                requested: parseInt(th.style.width, 10) || 0,
                rendered: th.offsetWidth,
            }), 500);
        });
    }');

    $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

    // The column has to be wider than the target to begin with, otherwise the
    // drag proves nothing.
    expect($data['natural'])->toBeGreaterThan(80)
        ->and($data['requested'])->toBe(60)
        ->and($data['rendered'])->toBeLessThanOrEqual(65);
});
