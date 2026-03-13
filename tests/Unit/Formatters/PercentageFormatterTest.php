<?php

use TeamNiftyGmbH\DataTable\Formatters\PercentageFormatter;

describe('PercentageFormatter', function (): void {
    it('returns empty string for null', function (): void {
        $formatter = new PercentageFormatter();

        expect($formatter->format(null))->toBe('');
    });

    it('formats a plain percentage value', function (): void {
        $formatter = new PercentageFormatter();

        expect($formatter->format(42))->toBe('42 %');
    });

    it('formats a decimal percentage', function (): void {
        $formatter = new PercentageFormatter();

        expect($formatter->format(42.5))->toContain('%');
    });

    it('formats zero percent', function (): void {
        $formatter = new PercentageFormatter();

        expect($formatter->format(0))->toBe('0 %');
    });

    it('formats 100 percent', function (): void {
        $formatter = new PercentageFormatter();

        expect($formatter->format(100))->toBe('100 %');
    });

    it('renders progress bar HTML', function (): void {
        $formatter = new PercentageFormatter(progressBar: true);
        $result = $formatter->format(50);

        expect($result)
            ->toContain('<div')
            ->toContain('width: 50.00%')
            ->toContain('bg-blue-600');
    });

    it('clamps progress bar to 0 for negative values', function (): void {
        $formatter = new PercentageFormatter(progressBar: true);
        $result = $formatter->format(-10);

        expect($result)->toContain('width: 0.00%');
    });

    it('clamps progress bar to 100 for values over 100', function (): void {
        $formatter = new PercentageFormatter(progressBar: true);
        $result = $formatter->format(150);

        expect($result)->toContain('width: 100.00%');
    });

    it('renders progress bar for 0 percent', function (): void {
        $formatter = new PercentageFormatter(progressBar: true);
        $result = $formatter->format(0);

        expect($result)->toContain('width: 0.00%');
    });

    it('renders progress bar for 100 percent', function (): void {
        $formatter = new PercentageFormatter(progressBar: true);
        $result = $formatter->format(100);

        expect($result)->toContain('width: 100.00%');
    });
});
