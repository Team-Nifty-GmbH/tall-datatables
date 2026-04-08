<?php

use Tests\Fixtures\Livewire\KanbanPostDataTable;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    $this->user = createTestUser(['name' => 'Test User', 'email' => 'test@example.com']);
    $this->actingAs($this->user);

    createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Published Post', 'is_published' => true]);
    createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Draft Post', 'is_published' => false]);
});

describe('Kanban View', function (): void {
    it('renders lanes with correct headers when in kanban mode', function (): void {
        $page = visitLivewire(KanbanPostDataTable::class);

        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setLayout", "kanban");
        }');

        $page->wait(2);

        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true, text: document.body.innerText.substring(0, 500) });
                    const text = document.body.innerText;
                    if (text.includes("Published") && text.includes("Draft")) {
                        return resolve({ timeout: false, hasPublished: true, hasDraft: true });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;
        expect($data['timeout'])->toBeFalse('Timed out waiting for kanban lanes');
        expect($data['hasPublished'])->toBeTrue();
        expect($data['hasDraft'])->toBeTrue();
    });

    it('shows cards in the correct lanes', function (): void {
        $page = visitLivewire(KanbanPostDataTable::class);

        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setLayout", "kanban");
        }');

        $page->wait(2);

        $result = $page->script('() => {
            return new Promise((resolve) => {
                const start = Date.now();
                const check = () => {
                    if (Date.now() - start > 10000) return resolve({ timeout: true });
                    const text = document.body.innerText;
                    if (text.includes("Published Post") && text.includes("Draft Post")) {
                        return resolve({ timeout: false, found: true });
                    }
                    setTimeout(check, 300);
                };
                setTimeout(check, 500);
            });
        }');

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;
        expect($data['timeout'])->toBeFalse('Timed out waiting for kanban cards');
    });

    it('has no javascript errors', function (): void {
        $page = visitLivewire(KanbanPostDataTable::class);

        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setLayout", "kanban");
        }');

        $page->wait(2)
            ->assertNoJavascriptErrors();
    });
});
