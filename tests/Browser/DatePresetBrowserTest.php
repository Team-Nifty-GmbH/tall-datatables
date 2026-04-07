<?php

use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Test User', 'email' => 'test@example.com']);
    $this->actingAs($this->user);

    createTestPost([
        'user_id' => $this->user->getKey(),
        'title' => 'Recent Post',
    ]);
});

describe('Date Preset Filter', function (): void {
    it('shows preset dropdown when a date column is selected in filter', function (): void {
        $page = visitLivewire(PostDataTable::class);

        $page->wait(2);

        // Open sidebar
        $page->script('() => {
            const buttons = document.querySelectorAll("button");
            for (const btn of buttons) {
                const onclick = btn.getAttribute("x-on:click") || "";
                if (onclick.includes("open.slide") && btn.closest("th")) {
                    btn.click();
                    break;
                }
            }
        }');

        $page->wait(1);

        // Select "created_at" as filter column via Livewire
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const selects = document.querySelectorAll("select");
                    for (const sel of selects) {
                        const options = Array.from(sel.options).map(o => o.value);
                        if (options.includes("created_at")) {
                            sel.value = "created_at";
                            sel.dispatchEvent(new Event("change", { bubbles: true }));
                            sel.dispatchEvent(new Event("input", { bubbles: true }));
                            return resolve({ timeout: false });
                        }
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $page->wait(1);

        // Check that preset dropdown with date options exists
        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true, options: [] });
                    const selects = document.querySelectorAll("select");
                    for (const sel of selects) {
                        const options = Array.from(sel.options).map(o => o.value);
                        if (options.includes("today") && options.includes("this_week")) {
                            return resolve({ timeout: false, found: true, options });
                        }
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;
        expect($data['timeout'])->toBeFalse('Timed out looking for date preset dropdown');
        expect($data['options'])->toContain('today')
            ->toContain('this_week')
            ->toContain('this_month')
            ->toContain('this_quarter')
            ->toContain('this_year')
            ->toContain('custom');
    });
});
