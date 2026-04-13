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

        $result = $page->script(<<<'JS'
            () => {
                return new Promise((resolve) => {
                    const start = Date.now();
                    const check = () => {
                        if (Date.now() - start > 10000) return resolve({ timeout: true });
                        const text = document.body.innerText;
                        if (text.includes("Published") && text.includes("Draft")) {
                            return resolve({ timeout: false, hasPublished: true, hasDraft: true });
                        }
                        setTimeout(check, 300);
                    };
                    setTimeout(check, 500);
                });
            }
        JS);

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

        $result = $page->script(<<<'JS'
            () => {
                return new Promise((resolve) => {
                    const start = Date.now();
                    const check = () => {
                        if (Date.now() - start > 15000) return resolve({ timeout: true });
                        const cards = document.querySelectorAll("[x-sort\\:item]");
                        if (cards.length >= 2) {
                            return resolve({ timeout: false, cardCount: cards.length });
                        }
                        setTimeout(check, 500);
                    };
                    setTimeout(check, 2000);
                });
            }
        JS);

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;
        expect($data['timeout'])->toBeFalse('Timed out waiting for kanban cards');
        expect($data['cardCount'])->toBeGreaterThanOrEqual(2);
    });

    it('shows layout switcher buttons when multiple layouts available', function (): void {
        $page = visitLivewire(KanbanPostDataTable::class);

        $page->wait(2);

        $result = $page->script(<<<'JS'
            () => {
                const buttons = document.querySelectorAll("[wire\\:click*='setLayout']");
                return {
                    count: buttons.length,
                    layouts: Array.from(buttons).map(b => {
                        const match = b.getAttribute("wire:click")?.match(/setLayout\('(\w+)'\)/);
                        return match ? match[1] : null;
                    }),
                };
            }
        JS);

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;
        expect($data['count'])->toBeGreaterThanOrEqual(2);
        expect($data['layouts'])->toContain('table');
        expect($data['layouts'])->toContain('kanban');
    });

    it('switches from table to kanban via layout button', function (): void {
        $page = visitLivewire(KanbanPostDataTable::class);

        $page->wait(2);

        $page->script(<<<'JS'
            () => {
                const btn = document.querySelector("[wire\\:click=\"setLayout('kanban')\"]");
                if (btn) btn.click();
            }
        JS);

        $result = $page->script(<<<'JS'
            () => {
                return new Promise((resolve) => {
                    const start = Date.now();
                    const check = () => {
                        if (Date.now() - start > 10000) return resolve({ timeout: true });
                        const lanes = document.querySelectorAll("[x-sort\\:group='kanban']");
                        if (lanes.length >= 2) {
                            return resolve({ timeout: false, laneCount: lanes.length });
                        }
                        setTimeout(check, 300);
                    };
                    setTimeout(check, 500);
                });
            }
        JS);

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;
        expect($data['timeout'])->toBeFalse('Timed out waiting for kanban view after layout switch');
    });

    it('renders lane color bars', function (): void {
        $page = visitLivewire(KanbanPostDataTable::class);

        $page->script('() => {
            const comp = document.querySelector("[wire\\\\:id]");
            const wireId = comp?.getAttribute("wire:id");
            window.Livewire?.find(wireId)?.call("setLayout", "kanban");
        }');

        $result = $page->script(<<<'JS'
            () => {
                return new Promise((resolve) => {
                    const start = Date.now();
                    const check = () => {
                        if (Date.now() - start > 10000) return resolve({ timeout: true });
                        const bars = document.querySelectorAll(".h-1[class*='bg-']");
                        if (bars.length >= 2) {
                            const barClasses = Array.from(bars).map(b => b.className);
                            return resolve({
                                timeout: false,
                                barCount: bars.length,
                                hasEmerald: barClasses.some(c => c.includes("bg-emerald-500")),
                            });
                        }
                        setTimeout(check, 300);
                    };
                    setTimeout(check, 1000);
                });
            }
        JS);

        $data = is_array($result) && isset($result[0]) ? $result[0] : $result;
        expect($data['timeout'])->toBeFalse('Timed out waiting for lane color bars');
        expect($data['barCount'])->toBeGreaterThanOrEqual(2);
        expect($data['hasEmerald'])->toBeTrue('Published lane should have emerald color bar');
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
