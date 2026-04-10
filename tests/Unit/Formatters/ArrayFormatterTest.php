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

    it('formats flat array of strings as badges', function (): void {
        $formatter = new ArrayFormatter();
        $result = $formatter->format(['foo', 'bar', 'baz']);

        expect($result)
            ->toContain('rounded-full')
            ->toContain('>foo</span>')
            ->toContain('>bar</span>')
            ->toContain('>baz</span>');
    });

    it('formats flat array of integers as badges', function (): void {
        $formatter = new ArrayFormatter();
        $result = $formatter->format([1, 2, 3]);

        expect($result)
            ->toContain('>1</span>')
            ->toContain('>2</span>')
            ->toContain('>3</span>');
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

    it('formats flat array with mixed scalar types as badges', function (): void {
        $formatter = new ArrayFormatter();
        $result = $formatter->format([1, 'two', 3.0, true]);

        expect($result)
            ->toContain('>1</span>')
            ->toContain('>two</span>')
            ->toContain('>3</span>');
    });

    it('handles associative array with scalar values as badges', function (): void {
        $formatter = new ArrayFormatter();
        $result = $formatter->format(['key' => 'Gruesse']);

        expect($result)->toContain('>Gruesse</span>');
    });

    it('filters null values from array', function (): void {
        $formatter = new ArrayFormatter();
        $result = $formatter->format(['2025-03-17', null, '2026-04-21']);

        expect($result)
            ->toContain('>2025-03-17</span>')
            ->toContain('>2026-04-21</span>')
            ->not->toContain('>null</span>');
    });

    it('returns empty string for array of only nulls', function (): void {
        $formatter = new ArrayFormatter();

        expect($formatter->format([null, null]))->toBe('');
    });
});
