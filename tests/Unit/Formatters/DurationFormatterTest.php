<?php

use TeamNiftyGmbH\DataTable\Formatters\DurationFormatter;

describe('DurationFormatter', function (): void {
    it('returns empty string for null', function (): void {
        $formatter = new DurationFormatter();

        expect($formatter->format(null))->toBe('');
    });

    it('formats zero milliseconds', function (): void {
        $formatter = new DurationFormatter();

        expect($formatter->format(0))->toBe('00:00');
    });

    it('formats one hour in milliseconds', function (): void {
        $formatter = new DurationFormatter();

        expect($formatter->format(3600000))->toBe('01:00');
    });

    it('formats hours and minutes', function (): void {
        $formatter = new DurationFormatter();

        // 2h 30m = 9_000_000ms
        expect($formatter->format(9000000))->toBe('02:30');
    });

    it('formats minutes only', function (): void {
        $formatter = new DurationFormatter();

        // 45m = 2_700_000ms
        expect($formatter->format(2700000))->toBe('00:45');
    });

    it('formats with seconds when enabled', function (): void {
        $formatter = new DurationFormatter(showSeconds: true);

        // 1h 30m 15s = 5_415_000ms
        expect($formatter->format(5415000))->toBe('01:30:15');
    });

    it('formats negative duration', function (): void {
        $formatter = new DurationFormatter();

        expect($formatter->format(-3600000))->toBe('-01:00');
    });

    it('handles string numeric values', function (): void {
        $formatter = new DurationFormatter();

        expect($formatter->format('7200000'))->toBe('02:00');
    });

    it('formats large durations beyond 24 hours', function (): void {
        $formatter = new DurationFormatter();

        // 25h = 90_000_000ms
        expect($formatter->format(90000000))->toBe('25:00');
    });
});
