<?php

use TeamNiftyGmbH\DataTable\Formatters\StringFormatter;

describe('StringFormatter', function (): void {
    it('returns empty string for null', function (): void {
        $formatter = new StringFormatter();

        expect($formatter->format(null))->toBe('');
    });

    it('formats a plain string', function (): void {
        $formatter = new StringFormatter();

        expect($formatter->format('hello'))->toBe('hello');
    });

    it('formats an integer as string', function (): void {
        $formatter = new StringFormatter();

        expect($formatter->format(42))->toBe('42');
    });

    it('formats a float as string', function (): void {
        $formatter = new StringFormatter();

        expect($formatter->format(3.14))->toBe('3.14');
    });

    it('escapes HTML entities', function (): void {
        $formatter = new StringFormatter();

        expect($formatter->format('<script>alert("xss")</script>'))
            ->toBe('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;');
    });

    it('escapes ampersands', function (): void {
        $formatter = new StringFormatter();

        expect($formatter->format('Foo & Bar'))->toBe('Foo &amp; Bar');
    });

    it('formats an empty string', function (): void {
        $formatter = new StringFormatter();

        expect($formatter->format(''))->toBe('');
    });
});
