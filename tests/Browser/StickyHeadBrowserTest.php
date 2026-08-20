<?php

/**
 * Browser Tests for the sticky column head
 *
 * The head cells carry `sticky top-0`, but whether that sticks depends on which
 * box scrolls. Only a real browser resolves that, so this cannot be asserted
 * server side.
 */

use Tests\Fixtures\Livewire\TallPostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Test User', 'email' => 'sticky-head@example.com']);

    foreach (range(1, 60) as $i) {
        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'Post Title ' . $i,
            'content' => 'Content ' . $i,
            'is_published' => true,
        ]);
    }
});

describe('Sticky column head', function (): void {
    it('keeps the head cells on screen while the page scrolls', function (): void {
        $page = visitLivewire(TallPostDataTable::class);

        $page->wait(3);

        $result = $page->script('async () => {
            const th = [...document.querySelectorAll("thead th")]
                .find(el => getComputedStyle(el).position === "sticky");

            if (! th) {
                return {error: "no-sticky-head-cell"};
            }

            const style = getComputedStyle(th);
            const before = th.getBoundingClientRect().top;

            window.scrollTo(0, 800);
            await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));

            return {
                position: style.position,
                top: style.top,
                documentScrolled: window.scrollY,
                headTopBefore: before,
                headTopAfter: th.getBoundingClientRect().top,
                docScrollable: document.documentElement.scrollHeight > window.innerHeight,
            };
        }');

        $data = is_array($result) ? ($result[0] ?? $result) : $result;

        expect($data['position'])->toBe('sticky');
        expect($data['documentScrolled'])->toBeGreaterThan(0);
        expect($data['headTopAfter'])->toBeGreaterThanOrEqual(0);
    });

    it('still scrolls a table that is wider than the screen', function (): void {
        $page = visitLivewire(TallPostDataTable::class)->on()->mobile();

        $page->wait(3);

        $result = $page->script('async () => {
            const wrapper = document.querySelector("table").parentElement;

            wrapper.scrollLeft = 200;
            await new Promise(r => requestAnimationFrame(() => requestAnimationFrame(r)));

            return {
                overflowX: getComputedStyle(wrapper).overflowX,
                scrolledLeft: wrapper.scrollLeft,
                wider: wrapper.scrollWidth > wrapper.clientWidth,
            };
        }');

        $data = is_array($result) ? ($result[0] ?? $result) : $result;

        expect($data['wider'])->toBeTrue();
        expect($data['overflowX'])->toBe('auto');
        expect($data['scrolledLeft'])->toBeGreaterThan(0);
    });
});
