<?php

use TeamNiftyGmbH\DataTable\Formatters\MoneyFormatter;

describe('MoneyFormatter', function (): void {
    it('returns empty string for null', function (): void {
        $formatter = new MoneyFormatter();

        expect($formatter->format(null))->toBe('');
    });

    it('formats a basic monetary value', function (): void {
        $formatter = new MoneyFormatter();
        $result = $formatter->format(100.00);

        expect($result)->toContain('100');
    });

    it('formats zero', function (): void {
        $formatter = new MoneyFormatter();
        $result = $formatter->format(0);

        expect($result)->toContain('0');
    });

    it('uses currency from context currency.iso', function (): void {
        $formatter = new MoneyFormatter();
        $result = $formatter->format(50.00, ['currency' => ['iso' => 'USD']]);

        expect(str_contains($result, '$') || str_contains($result, 'USD'))->toBeTrue();
    });

    it('uses currency from context currency_code', function (): void {
        $formatter = new MoneyFormatter();
        $result = $formatter->format(50.00, ['currency_code' => 'GBP']);

        expect(str_contains($result, '£') || str_contains($result, 'GBP'))->toBeTrue();
    });

    it('defaults to EUR when no currency in context', function (): void {
        $formatter = new MoneyFormatter();
        $result = $formatter->format(100.00);

        expect(str_contains($result, '€') || str_contains($result, 'EUR'))->toBeTrue();
    });

    it('wraps negative values in red span when colored', function (): void {
        $formatter = new MoneyFormatter(colored: true);
        $result = $formatter->format(-50.00);

        expect($result)->toContain('text-red-600');
    });

    it('wraps positive values in green span when colored', function (): void {
        $formatter = new MoneyFormatter(colored: true);
        $result = $formatter->format(50.00);

        expect($result)->toContain('text-green-600');
    });

    it('does not wrap zero in color span when colored', function (): void {
        $formatter = new MoneyFormatter(colored: true);
        $result = $formatter->format(0);

        expect($result)
            ->not->toContain('text-red-600')
            ->not->toContain('text-green-600');
    });

    it('does not add color span when not colored', function (): void {
        $formatter = new MoneyFormatter(colored: false);
        $result = $formatter->format(-50.00);

        expect($result)->not->toContain('text-red-600');
    });

    it('escapes XSS in currency code from context', function (): void {
        $formatter = new MoneyFormatter();
        $result = $formatter->format(50.00, ['currency_code' => '<script>alert(1)</script>']);

        expect($result)->not->toContain('<script>');
    });
});
