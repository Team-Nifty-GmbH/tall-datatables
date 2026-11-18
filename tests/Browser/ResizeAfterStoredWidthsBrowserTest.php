<?php

/**
 * A column may be dragged more than once.
 *
 * The first drag stores a width, and from then on the table lays itself out
 * fixed and every heading is clipped instead of wrapped. Both of those are
 * changes to the very cells the handle sits in, so the second drag is the one
 * that tells whether the handle survived them.
 */

use Tests\Fixtures\Livewire\WrappedResizablePostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Test User', 'email' => 'resize-twice@example.com']);

    for ($i = 1; $i <= 60; $i++) {
        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => "Post Title {$i}",
            'content' => "Content {$i}",
            'is_published' => true,
        ]);
    }
});

/**
 * What flux puts on top of a table: the row of labels is carried along by a
 * scroll driven animation so it stays in view. That animation transforms the
 * row the handles sit in, which is why the drag is worth trying underneath it.
 */
const FLOATING_HEAD_STYLES = <<<'CSS'
    [tall-datatable] thead tr:first-child {
        position: relative;
        z-index: 9;
        animation: flux-table-head linear both;
        animation-timeline: scroll(root block);
        animation-range: var(--flux-head-start, 0px) var(--flux-head-end, 0px);
    }

    [tall-datatable] thead tr:first-child > * {
        border-bottom-width: 0;
        box-shadow: inset 0 -1px 0 var(--color-gray-200);
    }

    @keyframes flux-table-head {
        to {
            transform: translateY(var(--flux-head-travel, 0px));
        }
    }
CSS;

it('lets a column be dragged again once a width is stored', function (): void {
    $page = visitLivewire(WrappedResizablePostDataTable::class);

    $page->wait(2);

    // The drag is aimed at the point the handle occupies, not at the handle
    // itself, and the event goes to whatever actually sits there. A handle that
    // has ended up underneath something else fails here the way it fails for a
    // real hand on a real mouse.
    $result = $page->script('() => {
        const drag = (distance) => {
            const handle = document.querySelectorAll(".cursor-col-resize")[0];
            if (! handle) return { error: "no handle" };

            const th = handle.closest("th");
            const rect = handle.getBoundingClientRect();
            const x = rect.left + rect.width / 2;
            const y = rect.top + rect.height / 2;
            const target = document.elementFromPoint(x, y);
            const before = th.offsetWidth;

            target.dispatchEvent(new MouseEvent("mousedown", { clientX: x, clientY: y, bubbles: true }));
            document.dispatchEvent(new MouseEvent("mousemove", { clientX: x + distance, clientY: y, bubbles: true }));
            document.dispatchEvent(new MouseEvent("mouseup", { clientX: x + distance, clientY: y, bubbles: true }));

            return {
                handleHit: target === handle,
                topmost: target ? target.tagName + "." + (target.className || "") : null,
                before,
                after: th.offsetWidth,
            };
        };

        return new Promise((resolve) => {
            const first = drag(120);

            // Long enough for the round trip that stores the width and for the
            // re-render that follows it.
            setTimeout(() => {
                const second = drag(120);

                setTimeout(() => resolve({ first, second }), 1500);
            }, 2500);
        });
    }');

    $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

    expect($data['first']['handleHit'])->toBeTrue()
        ->and($data['first']['after'])->toBeGreaterThan($data['first']['before']);

    expect($data['second']['handleHit'])->toBeTrue('the handle is no longer the topmost element at its own position, so no mouse can reach it')
        ->and($data['second']['after'])->toBeGreaterThan($data['second']['before']);
});

it('lets a column be dragged while the head floats above the rows', function (): void {
    $page = visitLivewire(WrappedResizablePostDataTable::class);

    $page->wait(2);

    $result = $page->script('() => {
        const style = document.createElement("style");
        style.textContent = ' . json_encode(FLOATING_HEAD_STYLES) . ';
        document.head.appendChild(style);

        const table = document.querySelector("table");
        const wrapper = table.closest("div");
        wrapper.setAttribute("tall-datatable", "");

        const head = table.querySelector("thead");
        const body = table.querySelector("tbody");
        const labels = head.rows[0];
        const headRect = head.getBoundingClientRect();
        const bodyRect = body.getBoundingClientRect();
        const travel = bodyRect.bottom - labels.offsetHeight - headRect.top;
        const start = Math.max(0, headRect.top + window.scrollY);

        labels.style.setProperty("--flux-head-start", start + "px");
        labels.style.setProperty("--flux-head-end", (start + travel) + "px");
        labels.style.setProperty("--flux-head-travel", travel + "px");

        window.scrollTo(0, Math.round(start + travel / 2));

        return new Promise((resolve) => {
            requestAnimationFrame(() => requestAnimationFrame(() => {
                const handle = document.querySelectorAll(".cursor-col-resize")[0];
                const th = handle.closest("th");
                const rect = handle.getBoundingClientRect();
                const x = rect.left + rect.width / 2;
                const y = rect.top + rect.height / 2;
                const target = document.elementFromPoint(x, y);
                const before = th.offsetWidth;

                target && target.dispatchEvent(new MouseEvent("mousedown", { clientX: x, clientY: y, bubbles: true }));
                document.dispatchEvent(new MouseEvent("mousemove", { clientX: x + 120, clientY: y, bubbles: true }));
                document.dispatchEvent(new MouseEvent("mouseup", { clientX: x + 120, clientY: y, bubbles: true }));

                resolve({
                    scrolled: window.scrollY,
                    travel,
                    handleHit: target === handle,
                    topmost: target ? target.tagName + "." + (target.className || "") : null,
                    before,
                    after: th.offsetWidth,
                });
            }));
        });
    }');

    $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

    expect($data['handleHit'])->toBeTrue('the floating head row has moved out from under its own handles')
        ->and($data['after'])->toBeGreaterThan($data['before']);
});
