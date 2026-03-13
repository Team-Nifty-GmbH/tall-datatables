<?php

use TeamNiftyGmbH\DataTable\Formatters\BooleanFormatter;

describe('BooleanFormatter', function (): void {
    it('returns empty string for null', function (): void {
        $formatter = new BooleanFormatter();

        expect($formatter->format(null))->toBe('');
    });

    it('returns green check icon for true', function (): void {
        $formatter = new BooleanFormatter();
        $result = $formatter->format(true);

        expect($result)
            ->toContain('text-green-500')
            ->toContain('<svg')
            ->toContain('</svg>');
    });

    it('returns red x icon for false', function (): void {
        $formatter = new BooleanFormatter();
        $result = $formatter->format(false);

        expect($result)
            ->toContain('text-red-500')
            ->toContain('<svg')
            ->toContain('</svg>');
    });

    it('returns green check icon for integer 1', function (): void {
        $formatter = new BooleanFormatter();
        $result = $formatter->format(1);

        expect($result)->toContain('text-green-500');
    });

    it('returns red x icon for integer 0', function (): void {
        $formatter = new BooleanFormatter();
        $result = $formatter->format(0);

        expect($result)->toContain('text-red-500');
    });

    it('true and false produce different outputs', function (): void {
        $formatter = new BooleanFormatter();

        expect($formatter->format(true))->not->toBe($formatter->format(false));
    });
});
