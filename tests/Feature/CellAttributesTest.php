<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\CellAttributesPostDataTable;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('Cell Attributes', function (): void {
    it('forwards cell attributes to rendered table cells', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(CellAttributesPostDataTable::class);
        $component->call('loadData');

        $html = $component->html();

        expect($html)->toContain('whitespace-normal');
        expect($html)->not->toMatch('/class="[^"]*whitespace-nowrap[^"]*whitespace-normal/');
    });

    it('applies truncation defaults when no cell attributes override whitespace', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class);
        $component->call('loadData');

        $html = $component->html();

        expect($html)->toContain('whitespace-nowrap');
    });

    it('preserves HtmlString values and renders them with line breaks', function (): void {
        createTestPost([
            'user_id' => $this->user->getKey(),
            'content' => "Line one\nLine two",
        ]);

        $component = Livewire::test(CellAttributesPostDataTable::class);
        $component->call('loadData');

        $component->assertSeeHtml('Line one<br />');
    });
});
