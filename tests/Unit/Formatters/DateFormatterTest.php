<?php

use Carbon\Carbon;
use TeamNiftyGmbH\DataTable\Formatters\DateFormatter;

describe('DateFormatter', function (): void {
    it('returns empty string for null', function (): void {
        $formatter = new DateFormatter;

        expect($formatter->format(null))->toBe('');
    });

    it('returns empty string for invalid date string', function (): void {
        $formatter = new DateFormatter;

        expect($formatter->format('not-a-date'))->toBe('');
    });

    it('formats date in date mode', function (): void {
        $formatter = new DateFormatter(mode: 'date');

        expect($formatter->format('2024-01-15'))->toBe('15.01.2024');
    });

    it('formats datetime in datetime mode', function (): void {
        $formatter = new DateFormatter(mode: 'datetime');

        expect($formatter->format('2024-01-15 14:30:00'))->toBe('15.01.2024 14:30');
    });

    it('formats time in time mode', function (): void {
        $formatter = new DateFormatter(mode: 'time');

        expect($formatter->format('2024-01-15 14:30:00'))->toBe('14:30');
    });

    it('formats relative with diffForHumans in relative mode', function (): void {
        $formatter = new DateFormatter(mode: 'relative');
        $result = $formatter->format(Carbon::now()->subHour());

        expect(
            str_contains($result, 'hour') ||
            str_contains($result, 'ago') ||
            str_contains($result, 'Stunde') ||
            str_contains($result, 'vor')
        )->toBeTrue();
    });

    it('uses custom format when provided', function (): void {
        $formatter = new DateFormatter(format: 'Y/m/d');

        expect($formatter->format('2024-01-15'))->toBe('2024/01/15');
    });

    it('custom format overrides mode', function (): void {
        $formatter = new DateFormatter(mode: 'date', format: 'Y');

        expect($formatter->format('2024-01-15'))->toBe('2024');
    });

    it('formats Carbon instance', function (): void {
        $formatter = new DateFormatter(mode: 'date');
        $carbon = Carbon::createFromDate(2024, 6, 15);

        expect($formatter->format($carbon))->toBe('15.06.2024');
    });

    it('formats unix timestamp', function (): void {
        $formatter = new DateFormatter(mode: 'date');
        $timestamp = Carbon::createFromDate(2024, 1, 15)->startOfDay()->timestamp;

        expect($formatter->format($timestamp))->toBe('15.01.2024');
    });

    it('uses datetime as default mode', function (): void {
        $formatter = new DateFormatter;

        expect($formatter->format('2024-01-15 14:30:00'))->toBe('15.01.2024 14:30');
    });
});
