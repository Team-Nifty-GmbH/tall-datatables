<?php

use TeamNiftyGmbH\DataTable\Formatters\FloatFormatter;

describe('FloatFormatter', function (): void {
    it('returns empty string for null', function (): void {
        $formatter = new FloatFormatter();

        expect($formatter->format(null))->toBe('');
    });

    it('formats a basic float', function (): void {
        $formatter = new FloatFormatter();
        $result = $formatter->format(1234.56);

        expect($result)->toContain('1.234')
            ->and($result)->toContain('56');
    });

    it('formats an integer as float with two decimal places', function (): void {
        $formatter = new FloatFormatter();
        $result = $formatter->format(100);

        expect($result)->toBe('100,00');
    });

    it('formats zero', function (): void {
        $formatter = new FloatFormatter();

        expect($formatter->format(0))->toBe('0,00');
    });

    it('wraps negative in red span when colored', function (): void {
        $formatter = new FloatFormatter(colored: true);
        $result = $formatter->format(-42.5);

        expect($result)->toContain('text-red-600');
    });

    it('wraps positive in green span when colored', function (): void {
        $formatter = new FloatFormatter(colored: true);
        $result = $formatter->format(42.5);

        expect($result)->toContain('text-green-600');
    });

    it('does not color zero', function (): void {
        $formatter = new FloatFormatter(colored: true);
        $result = $formatter->format(0);

        expect($result)
            ->not->toContain('text-red-600')
            ->not->toContain('text-green-600');
    });

    it('does not add color when not colored mode', function (): void {
        $formatter = new FloatFormatter(colored: false);
        $result = $formatter->format(-10.0);

        expect($result)->not->toContain('text-red-600');
    });
});
