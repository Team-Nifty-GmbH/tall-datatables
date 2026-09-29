<?php

use Illuminate\Support\Facades\App;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\UserDataTable;

beforeEach(function (): void {
    $this->actingAs(createTestUser());

    app('translator')->addJsonPath(__DIR__ . '/../Fixtures/lang');
});

/**
 * The count column of a relation is switched on by a bare checkbox in front of the
 * relation in the column menu, and its header used to read "Posts count" with "count"
 * never translated.
 */
it('translates the header of a relation count column', function (): void {
    App::setLocale('de');

    $labels = Livewire::test(UserDataTable::class)
        ->set('enabledCols', ['name', 'posts_count'])
        ->instance()
        ->getColLabels();

    expect($labels['posts_count'])->toBe('Beiträge (Anzahl)');
});

it('labels the relation count checkbox in the column menu', function (): void {
    App::setLocale('de');

    $html = Livewire::test(UserDataTable::class)->call('showSidebar')->html();

    expect($html)->toMatch("/relation\.name \+ '_count'.*?Anzahl.*?x-text=\"relation\.label\"/s");
});
