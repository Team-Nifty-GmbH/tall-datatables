<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    // Blade caches compiled templates by content hash, so a stale compile from
    // an earlier run would keep this test green even without the change.
    Artisan::call('view:clear');

    $this->user = createTestUser();
    $this->actingAs($this->user);
});

it('keeps the options sidebar out of the initial response', function (): void {
    Livewire::test(PostDataTable::class)
        ->assertSet('rendersSidebar', false)
        ->assertDontSee(__('Save filter'))
        ->assertDontSee(__('Adding to new OR group'));
});

it('renders the options sidebar once showSidebar was called', function (): void {
    Livewire::test(PostDataTable::class)
        ->call('showSidebar')
        ->assertSet('rendersSidebar', true)
        ->assertSee(__('Save filter'))
        ->assertSee(__('Adding to new OR group'));
});

it('sends markup back when showSidebar is called, so the slide is in the dom before it opens', function (): void {
    // Not #[Renderless]: the slide is teleported to <body>, so an island morph
    // would never reach the live node. The full re-render is what puts it there.
    $component = Livewire::test(PostDataTable::class)->call('showSidebar');

    expect($component->effects['html'] ?? null)->not->toBeNull();
});
