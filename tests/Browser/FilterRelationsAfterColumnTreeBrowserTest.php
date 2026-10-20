<?php

use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser();
    $this->actingAs($this->user);

    createTestPost(['user_id' => $this->user->getKey()]);
});

/**
 * The column tab walks its relation tree by replacing the relation list. The filter tab
 * used to render that same list, so after the column tree moved into user it offered the
 * relations of User while a filter is always built from the root model.
 */
it('keeps offering the root relations in the filter after the column tree moved into a relation', function (): void {
    $page = visitLivewire(PostDataTable::class);
    $page->wait(2);

    $page->script('() => {
        for (const btn of document.querySelectorAll("button")) {
            if ((btn.getAttribute("x-on:click") || "").includes("open.slide") && btn.closest("th")) {
                btn.click();
                break;
            }
        }
    }');
    $page->wait(1);

    $clickTab = fn (string $label) => $page->script('() => {
        for (const tab of document.querySelectorAll("button, [role=tab]")) {
            if (tab.textContent.trim() === "' . $label . '") {
                tab.click();
                break;
            }
        }
    }');

    $clickTab('Columns');
    $page->wait(1);

    $moved = $page->script('() => new Promise((resolve) => {
        const start = Date.now();
        const check = () => {
            const entry = Array.from(document.querySelectorAll("[x-on\\\\:click*=loadRelation]"))
                .find((el) => /\bUser\b/.test(el.textContent));
            if (entry) {
                entry.click();
                return resolve(true);
            }
            if (Date.now() - start > 10000) return resolve(false);
            setTimeout(check, 300);
        };
        check();
    })');
    expect(is_array($moved) ? $moved[0] : $moved)->toBeTrue();
    $page->wait(2);

    $clickTab('Filters');
    $page->wait(2);

    $options = $page->script('() => Array.from(
        document.querySelectorAll("select[name=new-filter-relation] option")
    ).map((option) => option.value)');
    $options = is_array($options) && isset($options[0]) && is_array($options[0]) ? $options[0] : $options;

    expect($options)->toContain('user', 'comments')
        ->not->toContain('posts');
});
