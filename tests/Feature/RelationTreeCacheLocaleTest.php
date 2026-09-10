<?php

use Illuminate\Support\Facades\App;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostWithRelationsDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);

    app('translator')->addJsonPath(__DIR__ . '/../Fixtures/lang');
});

function emailLabelUnder(string $locale): ?string
{
    App::setLocale($locale);

    $cols = Livewire::test(PostWithRelationsDataTable::class)->instance()->loadSlug('user')['cols'];

    return collect($cols)->firstWhere('col', 'email')['label'] ?? null;
}

/**
 * The column and relation lists are translated once and then cached. Without the locale in
 * the key, whoever opens the menu second reads the labels of whoever opened it first, so a
 * German colleague leaves an English user looking at German, and the entries that have no
 * German translation stay English in the same list.
 */
it('does not hand one locale the labels of another', function (): void {
    emailLabelUnder('de');

    expect(emailLabelUnder('en'))->toBe('Email');
});

it('keeps serving each locale its own labels', function (): void {
    emailLabelUnder('en');

    expect(emailLabelUnder('de'))->toBe('E Mail Adresse');
});
