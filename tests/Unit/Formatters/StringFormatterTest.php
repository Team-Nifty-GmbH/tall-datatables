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

    it('strips HTML tags and escapes remaining content', function (): void {
        $formatter = new StringFormatter();

        expect($formatter->format('<script>alert("xss")</script>'))
            ->toBe('alert(&quot;xss&quot;)');
    });

    it('strips rich text HTML to plain text', function (): void {
        $formatter = new StringFormatter();

        expect($formatter->format('<p>Hello <b>World</b></p>'))
            ->toBe('Hello World');
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
