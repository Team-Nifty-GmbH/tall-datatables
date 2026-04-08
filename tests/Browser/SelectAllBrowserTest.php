<?php

use Tests\Fixtures\Livewire\SelectablePostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Test User', 'email' => 'test@example.com']);
    $this->actingAs($this->user);

    for ($i = 1; $i <= 10; $i++) {
        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => "Post {$i}",
        ]);
    }
});

describe('Select All Checkbox', function (): void {
    it('checks all visible row checkboxes when select-all is clicked', function (): void {
        $page = visitLivewire(SelectablePostDataTable::class);

        $page->wait(2);

        // Click the select-all checkbox in the table header
        $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const headerCheckboxes = document.querySelectorAll("thead input[type=checkbox]");
                    for (const cb of headerCheckboxes) {
                        if (cb.value === "*") {
                            cb.click();
                            return resolve({ timeout: false, clicked: true });
                        }
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $page->wait(2);

        // Verify all row checkboxes in tbody are checked
        $result = $page->script('() => {
            const rowCheckboxes = document.querySelectorAll("tbody input[type=checkbox]");
            const total = rowCheckboxes.length;
            const checked = Array.from(rowCheckboxes).filter(cb => cb.checked).length;
            return { total, checked };
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($data['total'])->toBeGreaterThan(0);
        expect($data['checked'])->toBe($data['total']);
    });
});
