<?php

/**
 * A hand on a mouse does not land on the exact pixel of a column border, and a
 * lab reading long lists side by side works at seventy or even fifty percent
 * zoom, where every CSS pixel is worth half a real one. A grab area only a few
 * pixels wide is therefore not a small target, it is one that cannot be hit at
 * all, and the drag reads as a feature that does not exist.
 */

use Tests\Fixtures\Livewire\WrappedResizablePostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Test User', 'email' => 'reach@example.com']);

    for ($i = 1; $i <= 5; $i++) {
        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => "Post Title {$i}",
            'content' => "Content {$i}",
            'is_published' => true,
        ]);
    }
});

it('gives the drag a grab area a mouse can hit', function (): void {
    $page = visitLivewire(WrappedResizablePostDataTable::class);

    $page->wait(2);

    $result = $page->script('() => {
        const handle = document.querySelectorAll(".cursor-col-resize")[0];
        const rect = handle.getBoundingClientRect();

        return { width: rect.width, root: parseFloat(getComputedStyle(document.documentElement).fontSize) };
    }');

    $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

    // Eight pixels is what the platforms settle on for a border a pointer has
    // to catch, and it still fits inside the cell padding, so it never covers a
    // label.
    expect($data['width'])->toBeGreaterThanOrEqual(8);
});

it('resizes even when the drag starts a few pixels off the border', function (): void {
    $page = visitLivewire(WrappedResizablePostDataTable::class);

    $page->wait(2);

    $result = $page->script('() => {
        const handle = document.querySelectorAll(".cursor-col-resize")[0];
        const th = handle.closest("th");
        const rect = th.getBoundingClientRect();

        // Aimed six pixels short of the border, which is what a hand does.
        const x = rect.right - 6;
        const y = rect.top + rect.height / 2;
        const target = document.elementFromPoint(x, y);
        const before = th.offsetWidth;

        target && target.dispatchEvent(new MouseEvent("mousedown", { clientX: x, clientY: y, bubbles: true }));
        document.dispatchEvent(new MouseEvent("mousemove", { clientX: x + 120, clientY: y, bubbles: true }));
        document.dispatchEvent(new MouseEvent("mouseup", { clientX: x + 120, clientY: y, bubbles: true }));

        return {
            topmost: target ? target.tagName + "." + (target.className || "") : null,
            before,
            after: th.offsetWidth,
        };
    }');

    $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

    expect($data['after'])->toBeGreaterThan($data['before']);
});
