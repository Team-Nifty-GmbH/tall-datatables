<?php

/**
 * Browser Tests for Resizable Columns
 *
 * Tests the column resize interaction in a real browser via Playwright.
 * Requires: pestphp/pest-plugin-browser, built assets (npm run build).
 */

use Tests\Fixtures\Livewire\PostDataTable;
use Tests\Fixtures\Livewire\ResizablePostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Test User', 'email' => 'resize@example.com']);

    for ($i = 1; $i <= 5; $i++) {
        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => "Post Title {$i}",
            'content' => "Content {$i}",
            'is_published' => true,
        ]);
    }
});

describe('Resizable columns - disabled', function (): void {
    it('does not render resize handles when isResizable is false', function (): void {
        config()->set('tall-datatables.resizable_columns', false);

        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        $result = $page->script('() => {
            return document.querySelectorAll(".cursor-col-resize").length;
        }');

        $count = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($count)->toBe(0);
    });
});

describe('Resizable columns - enabled', function (): void {
    it('renders resize handles on column headers', function (): void {
        $page = visitLivewire(ResizablePostDataTable::class);

        $page->wait(2);

        $result = $page->script('() => {
            return document.querySelectorAll(".cursor-col-resize").length;
        }');

        $count = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($count)->toBeGreaterThan(0);
    });

    it('starts with table-auto class', function (): void {
        $page = visitLivewire(ResizablePostDataTable::class);

        $page->wait(2);

        $result = $page->script('() => {
            const table = document.querySelector("table");
            return table?.classList.contains("table-auto");
        }');

        $hasClass = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($hasClass)->toBeTrue();
    });

    it('switches to table-fixed and sets width on drag', function (): void {
        $page = visitLivewire(ResizablePostDataTable::class);

        $page->wait(2);

        // Simulate a column resize drag on the first resizable column
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const handle = document.querySelector(".cursor-col-resize");
                if (!handle) return resolve({ error: "no handle found" });

                const th = handle.closest("th");
                const table = th.closest("table");
                const startWidth = th.offsetWidth;
                const rect = handle.getBoundingClientRect();

                // Simulate mousedown
                handle.dispatchEvent(new MouseEvent("mousedown", {
                    clientX: rect.left,
                    clientY: rect.top + rect.height / 2,
                    bubbles: true,
                }));

                // Simulate mousemove (drag 100px right)
                document.dispatchEvent(new MouseEvent("mousemove", {
                    clientX: rect.left + 100,
                    clientY: rect.top + rect.height / 2,
                    bubbles: true,
                }));

                // Simulate mouseup
                document.dispatchEvent(new MouseEvent("mouseup", {
                    clientX: rect.left + 100,
                    clientY: rect.top + rect.height / 2,
                    bubbles: true,
                }));

                // Wait for Livewire to process
                setTimeout(() => {
                    resolve({
                        isTableFixed: table.classList.contains("table-fixed"),
                        hasWidth: !!th.style.width,
                        newWidth: parseInt(th.style.width, 10) || 0,
                        startWidth: startWidth,
                    });
                }, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['isTableFixed'])->toBeTrue();
        expect($data['hasWidth'])->toBeTrue();
        expect($data['newWidth'])->toBeGreaterThan(0);
    });

    it('enforces minimum column width of 50px', function (): void {
        $page = visitLivewire(ResizablePostDataTable::class);

        $page->wait(2);

        $result = $page->script('() => {
            return new Promise((resolve) => {
                const handle = document.querySelector(".cursor-col-resize");
                if (!handle) return resolve({ error: "no handle found" });

                const th = handle.closest("th");
                const rect = handle.getBoundingClientRect();

                // Simulate mousedown
                handle.dispatchEvent(new MouseEvent("mousedown", {
                    clientX: rect.left,
                    clientY: rect.top + rect.height / 2,
                    bubbles: true,
                }));

                // Drag far left to shrink below minimum
                document.dispatchEvent(new MouseEvent("mousemove", {
                    clientX: rect.left - 1000,
                    clientY: rect.top + rect.height / 2,
                    bubbles: true,
                }));

                document.dispatchEvent(new MouseEvent("mouseup", {
                    clientX: rect.left - 1000,
                    bubbles: true,
                }));

                setTimeout(() => {
                    resolve({
                        width: parseInt(th.style.width, 10) || 0,
                    });
                }, 200);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['width'])->toBeGreaterThanOrEqual(50);
    });

    it('persists column widths to Livewire after resize', function (): void {
        $page = visitLivewire(ResizablePostDataTable::class);

        $page->wait(2);

        // Perform resize and check Livewire state
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const handle = document.querySelector(".cursor-col-resize");
                if (!handle) return resolve({ error: "no handle found" });

                const th = handle.closest("th");
                const rect = handle.getBoundingClientRect();

                handle.dispatchEvent(new MouseEvent("mousedown", {
                    clientX: rect.left,
                    clientY: rect.top + rect.height / 2,
                    bubbles: true,
                }));

                document.dispatchEvent(new MouseEvent("mousemove", {
                    clientX: rect.left + 50,
                    clientY: rect.top + rect.height / 2,
                    bubbles: true,
                }));

                document.dispatchEvent(new MouseEvent("mouseup", {
                    clientX: rect.left + 50,
                    bubbles: true,
                }));

                // Wait for Livewire to sync
                setTimeout(() => {
                    const comp = document.querySelector("[wire\\\\:id]");
                    const wireId = comp?.getAttribute("wire:id");
                    const colWidths = window.Livewire?.find(wireId)?.get("colWidths") || {};
                    resolve({
                        hasWidths: Object.keys(colWidths).length > 0,
                        colWidths: colWidths,
                    });
                }, 2000);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['hasWidths'])->toBeTrue();
    });

    it('has no JavaScript errors during resize', function (): void {
        $page = visitLivewire(ResizablePostDataTable::class);

        $page->wait(2);

        // Perform a resize
        $page->script('() => {
            return new Promise((resolve) => {
                const handle = document.querySelector(".cursor-col-resize");
                if (!handle) return resolve();

                const rect = handle.getBoundingClientRect();

                handle.dispatchEvent(new MouseEvent("mousedown", {
                    clientX: rect.left, clientY: rect.top, bubbles: true,
                }));
                document.dispatchEvent(new MouseEvent("mousemove", {
                    clientX: rect.left + 50, clientY: rect.top, bubbles: true,
                }));
                document.dispatchEvent(new MouseEvent("mouseup", {
                    clientX: rect.left + 50, bubbles: true,
                }));

                setTimeout(resolve, 500);
            });
        }');

        $page->assertNoJavascriptErrors();
    });
});
