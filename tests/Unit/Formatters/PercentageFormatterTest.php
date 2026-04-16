<?php

use TeamNiftyGmbH\DataTable\Formatters\PercentageFormatter;

describe('PercentageFormatter', function (): void {
    it('returns empty string for null', function (): void {
        $formatter = new PercentageFormatter();

        expect($formatter->format(null))->toBe('');
    });

    it('formats a decimal fraction as percentage', function (): void {
        $formatter = new PercentageFormatter();

        expect($formatter->format(0.42))->toBe('42 %');
    });

    it('formats a decimal fraction with decimals', function (): void {
        $formatter = new PercentageFormatter();

        expect($formatter->format(0.195))->toBe('19,50 %');
    });

    it('formats zero', function (): void {
        $formatter = new PercentageFormatter();

        expect($formatter->format(0))->toBe('0 %');
    });

    it('formats 1.0 as 100 percent', function (): void {
        $formatter = new PercentageFormatter();

        expect($formatter->format(1))->toBe('100 %');
    });

    it('renders progress bar with label and indigo color', function (): void {
        $formatter = new PercentageFormatter(progressBar: true);
        $result = $formatter->format(0.5);

        expect($result)
            ->toContain('<div')
            ->toContain('width: 50.00%')
            ->toContain('bg-indigo-500')
            ->toContain('50 %');
    });

    it('clamps progress bar to 0 for negative values', function (): void {
        $formatter = new PercentageFormatter(progressBar: true);
        $result = $formatter->format(-0.1);

        expect($result)->toContain('width: 0.00%');
    });

    it('clamps progress bar to 100 for values over 100', function (): void {
        $formatter = new PercentageFormatter(progressBar: true);
        $result = $formatter->format(1.5);

        expect($result)->toContain('width: 100.00%');
    });

    it('renders progress bar for 0 percent', function (): void {
        $formatter = new PercentageFormatter(progressBar: true);
        $result = $formatter->format(0);

        expect($result)->toContain('width: 0.00%');
    });

    it('renders progress bar for 100 percent', function (): void {
        $formatter = new PercentageFormatter(progressBar: true);
        $result = $formatter->format(1);

        expect($result)->toContain('width: 100.00%');
    });

    it('renders text percentage with multiplier 1 for raw values', function (): void {
        $formatter = new PercentageFormatter(multiplier: 1);

        expect($formatter->format(75))->toBe('75 %');
    });
});
