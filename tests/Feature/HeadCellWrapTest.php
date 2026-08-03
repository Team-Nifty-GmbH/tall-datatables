<?php

use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Tests\Fixtures\Livewire\PostDataTable;
use Tests\Fixtures\Livewire\WrappedLabelsPostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

// A column is otherwise never narrower than its own heading: the automatic
// layout takes the widest thing in the column and a heading on one line cannot
// break. A column labelled "Oligo Purification Method" holding "HPLC" then takes
// three times the room it needs, which is what limits how much fits on screen.
/**
 * The th of a column heading, told apart from the select, score and action
 * cells by the sticky binding only it carries.
 */
function columnHeadCell(string $html): string
{
    expect(preg_match('/<th[^>]*\$wire\.stickyCols[^>]*>/', $html, $matches))->toBe(1);

    return $matches[0];
}

describe('Column label wrapping', function (): void {
    it('keeps column labels on one line by default', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(PostDataTable::class);
        $component->call('loadData');

        expect(PostDataTable::$wrapColumnLabels)->toBeFalse()
            ->and(columnHeadCell($component->html()))->toContain('whitespace-nowrap');
    });

    it('lets a data table opt its column labels into wrapping', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(WrappedLabelsPostDataTable::class);
        $component->call('loadData');

        expect(WrappedLabelsPostDataTable::$wrapColumnLabels)->toBeTrue()
            ->and(columnHeadCell($component->html()))->not->toContain('whitespace-nowrap');
    });

    // Only the column headings are affected. The cells around them, the select
    // box and the actions, are meant to stay on one line whatever the table says.
    it('leaves the other head cells on one line when a table opts in', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $component = Livewire::test(WrappedLabelsPostDataTable::class);
        $component->call('loadData');

        expect($component->html())->toContain('relative table-cell whitespace-nowrap px-3');
    });

    it('renders a head cell on one line unless it is told to wrap', function (): void {
        $plain = Blade::render('<x-tall-datatables::table.head-cell>Oligo Purification Method</x-tall-datatables::table.head-cell>');
        $wrapping = Blade::render('<x-tall-datatables::table.head-cell :wrap="true">Oligo Purification Method</x-tall-datatables::table.head-cell>');

        expect($plain)->toContain('whitespace-nowrap')
            ->and($wrapping)->not->toContain('whitespace-nowrap');
    });

    // The values keep their single line and their cap either way, otherwise a
    // long sequence would push every other column off the screen.
    it('leaves the data cells on one line', function (): void {
        $html = Blade::render('<x-tall-datatables::table.cell>ACGTACGTACGT</x-tall-datatables::table.cell>');

        expect($html)->toContain('whitespace-nowrap')
            ->and($html)->toContain('max-w-xs');
    });
});
