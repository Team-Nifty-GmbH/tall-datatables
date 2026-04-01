<?php

use TeamNiftyGmbH\DataTable\Formatters\ArrayFormatter;

describe('ArrayFormatter', function (): void {
    it('returns empty string for null', function (): void {
        $formatter = new ArrayFormatter();

        expect($formatter->format(null))->toBe('');
    });

    it('returns empty string for empty array', function (): void {
        $formatter = new ArrayFormatter();

        expect($formatter->format([]))->toBe('');
    });

    it('formats flat array of strings as comma separated', function (): void {
        $formatter = new ArrayFormatter();

        expect($formatter->format(['foo', 'bar', 'baz']))->toBe('foo, bar, baz');
    });

    it('formats flat array of integers as comma separated', function (): void {
        $formatter = new ArrayFormatter();

        expect($formatter->format([1, 2, 3]))->toBe('1, 2, 3');
    });

    it('escapes HTML in scalar values', function (): void {
        $formatter = new ArrayFormatter();

        $result = $formatter->format(['<script>alert(1)</script>', 'safe']);

        expect($result)
            ->not->toContain('<script>')
            ->toContain('&lt;script&gt;')
            ->toContain('safe');
    });

    it('renders nested array as pre with JSON', function (): void {
        $formatter = new ArrayFormatter();
        $result = $formatter->format(['nested' => ['a', 'b']]);

        expect($result)
            ->toContain('<pre')
            ->toContain('nested')
            ->toContain('</pre>');
    });

    it('escapes JSON content in pre tag', function (): void {
        $formatter = new ArrayFormatter();
        $result = $formatter->format(['key' => '<script>evil()</script>']);

        expect($result)->not->toContain('<script>');
    });

    it('renders object array as pre with JSON', function (): void {
        $formatter = new ArrayFormatter();
        $result = $formatter->format([['name' => 'item1'], ['name' => 'item2']]);

        expect($result)->toContain('<pre');
    });

    it('casts non-array values to escaped string', function (): void {
        $formatter = new ArrayFormatter();

        expect($formatter->format('plain string'))->toBe('plain string');
        expect($formatter->format(42))->toBe('42');
        expect($formatter->format(3.14))->toBe('3.14');
        expect($formatter->format(true))->toBe('1');
    });

    it('escapes HTML in non-array scalar values', function (): void {
        $formatter = new ArrayFormatter();

        $result = $formatter->format('<script>alert(1)</script>');

        expect($result)->not->toContain('<script>')
            ->toContain('&lt;script&gt;');
    });

    it('formats flat array with mixed scalar types', function (): void {
        $formatter = new ArrayFormatter();

        $result = $formatter->format([1, 'two', 3.0, true]);

        expect($result)->toBe('1, two, 3, 1');
    });

    it('handles associative array with scalar values as flat', function (): void {
        $formatter = new ArrayFormatter();
        $result = $formatter->format(['key' => 'Gruesse']);

        // Associative arrays with scalar values are treated as flat
        expect($result)->toBe('Gruesse');
    });
});
