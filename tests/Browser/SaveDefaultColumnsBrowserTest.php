<?php

/**
 * Browser Tests for Set as Default
 *
 * The button opens a TallStackUI dialog and only saves from inside its
 * callbacks, so the server side alone proves nothing: the click has to travel
 * through the dialog before saveDefaultColumns() is ever reached.
 */

use TeamNiftyGmbH\DataTable\Models\DatatableUserSetting;
use Tests\Fixtures\Livewire\DefaultColumnsPostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Default User', 'email' => 'default-columns@example.com']);
    $this->actingAs($this->user);

    createTestPost([
        'user_id' => $this->user->getKey(),
        'title' => 'Post Title 1',
        'content' => 'Content 1',
        'is_published' => true,
    ]);
});

describe('Set as Default', function (): void {
    it('stores the global default columns from the dialog', function (): void {
        $page = visitLivewire(DefaultColumnsPostDataTable::class);

        $page->wait(2);

        $result = $page->script('async () => {
            const clickByText = (text) => {
                const button = [...document.querySelectorAll("button")]
                    .find((candidate) => candidate.textContent.trim() === text);

                button?.click();

                return !! button;
            };

            await window.Livewire.all()[0].$wire.showSidebar();
            await new Promise((resolve) => setTimeout(resolve, 1000));

            if (! clickByText("Set as Default")) {
                return "no-set-as-default-button";
            }

            await new Promise((resolve) => setTimeout(resolve, 1000));

            if (! clickByText("Save default only")) {
                return "no-dialog-button";
            }

            await new Promise((resolve) => setTimeout(resolve, 1500));

            return "clicked";
        }');

        $raw = is_array($result) && isset($result[0]) ? $result[0] : $result;

        expect($raw)->toBe('clicked')
            ->and(DatatableUserSetting::where('is_default_columns', true)->count())->toBe(1);
    });
});
