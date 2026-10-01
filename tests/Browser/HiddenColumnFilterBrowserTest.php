<?php

use Tests\Fixtures\Livewire\DateFormatterPostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Test User', 'email' => 'hidden-column-filter@example.com']);

    createTestPost([
        'user_id' => $this->user->getKey(),
        'title' => 'Post Title',
        'is_published' => true,
    ]);
});

it('offers yes and no for a boolean column that is not shown', function (): void {
    $page = visitLivewire(DateFormatterPostDataTable::class);

    $page->wait(2);

    $result = $page->script('async () => {
        const opener = [...document.querySelectorAll("[x-on\\\\:click]")]
            .find((el) => (el.getAttribute("x-on:click") || "").includes("showSidebar"));

        if (! opener) {
            return "no-opener";
        }

        opener.click();
        await new Promise((resolve) => setTimeout(resolve, 2500));

        const el = document.querySelector(\'[x-data^="datatableOptions"]\');

        if (! el) {
            return "no-options-component";
        }

        const options = window.Alpine.$data(el);
        options.newFilter.column = "is_published";
        await new Promise((resolve) => setTimeout(resolve, 300));

        return JSON.stringify({
            shown: options.enabledCols.includes("is_published"),
            type: options.filterSelectType,
            operator: options.newFilter.operator,
        });
    }');

    $raw = is_array($result) && isset($result[0]) ? $result[0] : $result;

    expect($raw)->not->toBe('no-opener')
        ->and($raw)->not->toBe('no-options-component');

    $state = json_decode((string) $raw, true);

    expect($state['shown'])->toBeFalse()
        ->and($state['type'])->toBe('valueList')
        ->and($state['operator'])->toBe('=');
});
