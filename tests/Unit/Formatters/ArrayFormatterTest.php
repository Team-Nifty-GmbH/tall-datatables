<?php

use TeamNiftyGmbH\DataTable\Formatters\ArrayFormatter;

describe('ArrayFormatter', function (): void {
    it('returns empty string for null', function (): void {
        $formatter = new ArrayFormatter;

        expect($formatter->format(null))->toBe('');
    });

    it('returns empty string for empty array', function (): void {
        $formatter = new ArrayFormatter;

        expect($formatter->format([]))->toBe('');
    });

    it('formats flat array of strings as comma separated', function (): void {
        $formatter = new ArrayFormatter;

        expect($formatter->format(['foo', 'bar', 'baz']))->toBe('foo, bar, baz');
    });

    it('formats flat array of integers as comma separated', function (): void {
        $formatter = new ArrayFormatter;

        expect($formatter->format([1, 2, 3]))->toBe('1, 2, 3');
    });

    it('escapes HTML in scalar values', function (): void {
        $formatter = new ArrayFormatter;

        $result = $formatter->format(['<script>alert(1)</script>', 'safe']);

        expect($result)
            ->not->toContain('<script>')
            ->toContain('&lt;script&gt;')
            ->toContain('safe');
    });

    it('renders nested array as pre with JSON', function (): void {
        $formatter = new ArrayFormatter;
        $result = $formatter->format(['nested' => ['a', 'b']]);

        expect($result)
            ->toContain('<pre')
            ->toContain('nested')
            ->toContain('</pre>');
    });

    it('escapes JSON content in pre tag', function (): void {
        $formatter = new ArrayFormatter;
        $result = $formatter->format(['key' => '<script>evil()</script>']);

        expect($result)->not->toContain('<script>');
    });

    it('renders object array as pre with JSON', function (): void {
        $formatter = new ArrayFormatter;
        $result = $formatter->format([['name' => 'item1'], ['name' => 'item2']]);

        expect($result)->toContain('<pre');
    });
});
