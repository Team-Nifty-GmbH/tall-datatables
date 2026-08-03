<?php

use Illuminate\Support\Facades\Blade;

// A column was always at least as wide as its label, because the head cell was
// not allowed to wrap. With the automatic layout the label therefore decided the
// width of the whole column, even where the values below it are short: a column
// labelled "Oligo Purification Method" holding "HPLC" took three times the room
// it needed. That is what limits how much of a table fits on screen.
describe('Head cell wrapping', function (): void {
    it('lets a column label wrap so the label no longer dictates the column width', function (): void {
        $html = Blade::render('<x-tall-datatables::table.head-cell>Oligo Purification Method</x-tall-datatables::table.head-cell>');

        expect($html)->not->toContain('whitespace-nowrap');
    });

    it('keeps a head cell on one line when it asks for that itself', function (): void {
        $html = Blade::render('<x-tall-datatables::table.head-cell class="whitespace-nowrap">Score</x-tall-datatables::table.head-cell>');

        expect($html)->toContain('whitespace-nowrap');
    });

    // The values keep their single line and their cap, otherwise a long sequence
    // would push every other column off the screen.
    it('leaves the data cells on one line', function (): void {
        $html = Blade::render('<x-tall-datatables::table.cell>ACGTACGTACGT</x-tall-datatables::table.cell>');

        expect($html)->toContain('whitespace-nowrap')
            ->and($html)->toContain('max-w-xs');
    });
});
