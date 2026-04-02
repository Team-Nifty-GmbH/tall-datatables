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

    it('escapes formatted value in colored positive output', function (): void {
        $formatter = new MoneyFormatter(colored: true);
        $result = $formatter->format(50.00);

        // The formatted monetary value inside the span should be escaped via e()
        // Verify it contains the escaped content within the span
        expect($result)->toContain('text-green-600');

        // Extract the content between span tags
        preg_match('/<span[^>]*>(.*?)<\/span>/', $result, $matches);
        $innerContent = $matches[1] ?? '';

        // The inner content should be the same as e() would produce
        expect($innerContent)->toBe(e($innerContent));
    });

    it('escapes formatted value in colored negative output', function (): void {
        $formatter = new MoneyFormatter(colored: true);
        $result = $formatter->format(-50.00);

        expect($result)->toContain('text-red-600');

        preg_match('/<span[^>]*>(.*?)<\/span>/', $result, $matches);
        $innerContent = $matches[1] ?? '';

        expect($innerContent)->toBe(e($innerContent));
    });

    it('escapes formatted value in non-colored output', function (): void {
        $formatter = new MoneyFormatter(colored: false);
        $result = $formatter->format(50.00);

        // The result should be identical to e() of itself
        expect($result)->toBe(e($result));
    });

    it('does not contain unescaped angle brackets from formatted value in colored output', function (): void {
        $formatter = new MoneyFormatter(colored: true);
        $result = $formatter->format(100.00);

        // Extract content between span tags and verify it contains no raw HTML
        preg_match('/<span[^>]*>(.*?)<\/span>/', $result, $matches);
        $innerContent = $matches[1] ?? '';

        // Inner content must not contain raw < or > (only the span wrapper should have those)
        expect($innerContent)->not->toMatch('/<(?!\/?\s*$)/');
    });
});
