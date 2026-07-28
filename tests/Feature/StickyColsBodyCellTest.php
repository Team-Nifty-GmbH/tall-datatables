<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

it('binds the sticky class of body cells to stickyCols so no re-render is needed', function (): void {
    createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Sticky Row']);

    $html = Livewire::test(PostDataTable::class)->call('loadData')->html();

    preg_match('/<td[^>]*data-column="title"[^>]*>/', $html, $matches);

    expect($matches[0] ?? '')
        ->toContain('x-bind:class="($wire.stickyCols || []).includes(\'title\') ? \'sticky left-0 border-r')
        ->toContain('x-bind:style="($wire.stickyCols || []).includes(\'title\') ? \'z-index: 2\' : \'\'"');
});
