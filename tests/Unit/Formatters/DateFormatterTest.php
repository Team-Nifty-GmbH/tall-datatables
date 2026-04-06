<?php

use Carbon\Carbon;
use TeamNiftyGmbH\DataTable\Formatters\DateFormatter;

describe('DateFormatter', function (): void {
    it('returns empty string for null', function (): void {
        $formatter = new DateFormatter();

        expect($formatter->format(null))->toBe('');
    });

    it('returns empty string for invalid date string', function (): void {
        $formatter = new DateFormatter();

        expect($formatter->format('not-a-date'))->toBe('');
    });

    it('formats date in date mode using locale', function (): void {
        $formatter = new DateFormatter(mode: 'date');

        expect($formatter->format('2024-01-15', ['_locale' => 'de']))->toBe('15.01.2024');
        expect($formatter->format('2024-01-15', ['_locale' => 'en']))->toBe('01/15/2024');
    });

    it('formats datetime in datetime mode using locale', function (): void {
        $formatter = new DateFormatter(mode: 'datetime');

        expect($formatter->format('2024-01-15 14:30:00', ['_locale' => 'de']))->toBe('15.01.2024 14:30');
        expect($formatter->format('2024-01-15 14:30:00', ['_locale' => 'en']))->toBe('01/15/2024 2:30 PM');
    });

    it('formats time in time mode using locale', function (): void {
        $formatter = new DateFormatter(mode: 'time');

        expect($formatter->format('2024-01-15 14:30:00', ['_locale' => 'de']))->toBe('14:30');
        expect($formatter->format('2024-01-15 14:30:00', ['_locale' => 'en']))->toBe('2:30 PM');
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

    it('uses custom format when provided (bypasses locale)', function (): void {
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

        expect($formatter->format($carbon, ['_locale' => 'de']))->toBe('15.06.2024');
    });

    it('formats unix timestamp', function (): void {
        $formatter = new DateFormatter(mode: 'date');
        $timestamp = Carbon::createFromDate(2024, 1, 15)->startOfDay()->timestamp;

        expect($formatter->format($timestamp, ['_locale' => 'de']))->toBe('15.01.2024');
    });

    it('falls back to app locale when no locale in context', function (): void {
        app()->setLocale('de');
        $formatter = new DateFormatter(mode: 'date');

        expect($formatter->format('2024-01-15'))->toBe('15.01.2024');

        app()->setLocale('en');

        expect($formatter->format('2024-01-15'))->toBe('01/15/2024');
    });

    describe('timezone conversion', function (): void {
        it('converts from database timezone to display timezone', function (): void {
            $formatter = new DateFormatter(mode: 'datetime');

            $result = $formatter->format('2024-01-15 14:30:00', [
                '_dbTimezone' => 'UTC',
                '_displayTimezone' => 'Europe/Berlin',
                '_locale' => 'de',
            ]);

            expect($result)->toBe('15.01.2024 15:30');
        });

        it('converts from database timezone to display timezone in summer (DST)', function (): void {
            $formatter = new DateFormatter(mode: 'datetime');

            $result = $formatter->format('2024-07-15 14:30:00', [
                '_dbTimezone' => 'UTC',
                '_displayTimezone' => 'Europe/Berlin',
                '_locale' => 'de',
            ]);

            expect($result)->toBe('15.07.2024 16:30');
        });

        it('converts time mode with timezone', function (): void {
            $formatter = new DateFormatter(mode: 'time');

            $result = $formatter->format('2024-01-15 23:30:00', [
                '_dbTimezone' => 'UTC',
                '_displayTimezone' => 'Europe/Berlin',
                '_locale' => 'de',
            ]);

            expect($result)->toBe('00:30');
        });

        it('converts date mode with timezone (date can shift)', function (): void {
            $formatter = new DateFormatter(mode: 'date');

            $result = $formatter->format('2024-01-15 23:30:00', [
                '_dbTimezone' => 'UTC',
                '_displayTimezone' => 'Europe/Berlin',
                '_locale' => 'de',
            ]);

            expect($result)->toBe('16.01.2024');
        });

        it('converts with custom format and timezone', function (): void {
            $formatter = new DateFormatter(format: 'Y-m-d H:i:s');

            $result = $formatter->format('2024-01-15 14:30:00', [
                '_dbTimezone' => 'UTC',
                '_displayTimezone' => 'America/New_York',
            ]);

            expect($result)->toBe('2024-01-15 09:30:00');
        });

        it('converts Carbon instance with timezone context', function (): void {
            $formatter = new DateFormatter(mode: 'datetime');
            $carbon = Carbon::parse('2024-01-15 14:30:00', 'UTC');

            $result = $formatter->format($carbon, [
                '_dbTimezone' => 'UTC',
                '_displayTimezone' => 'Europe/Berlin',
                '_locale' => 'de',
            ]);

            expect($result)->toBe('15.01.2024 15:30');
        });

        it('handles same database and display timezone', function (): void {
            $formatter = new DateFormatter(mode: 'datetime');

            $result = $formatter->format('2024-01-15 14:30:00', [
                '_dbTimezone' => 'Europe/Berlin',
                '_displayTimezone' => 'Europe/Berlin',
                '_locale' => 'de',
            ]);

            expect($result)->toBe('15.01.2024 14:30');
        });
    });

    describe('locale formatting', function (): void {
        it('formats date in English locale', function (): void {
            $formatter = new DateFormatter(mode: 'date');

            expect($formatter->format('2024-01-15', ['_locale' => 'en']))->toBe('01/15/2024');
        });

        it('formats datetime in English locale', function (): void {
            $formatter = new DateFormatter(mode: 'datetime');

            expect($formatter->format('2024-01-15 14:30:00', ['_locale' => 'en']))->toBe('01/15/2024 2:30 PM');
        });

        it('formats time in English locale', function (): void {
            $formatter = new DateFormatter(mode: 'time');

            expect($formatter->format('2024-01-15 14:30:00', ['_locale' => 'en']))->toBe('2:30 PM');
        });

        it('formats date in French locale', function (): void {
            $formatter = new DateFormatter(mode: 'date');

            expect($formatter->format('2024-01-15', ['_locale' => 'fr']))->toBe('15/01/2024');
        });

        it('locale context overrides app locale', function (): void {
            app()->setLocale('en');
            $formatter = new DateFormatter(mode: 'date');

            expect($formatter->format('2024-01-15', ['_locale' => 'de']))->toBe('15.01.2024');
        });

        it('same formatter switches output by locale context for date mode', function (): void {
            $formatter = new DateFormatter(mode: 'date');

            expect($formatter->format('2024-01-15', ['_locale' => 'de']))->toBe('15.01.2024')
                ->and($formatter->format('2024-01-15', ['_locale' => 'en']))->toBe('01/15/2024')
                ->and($formatter->format('2024-01-15', ['_locale' => 'fr']))->toBe('15/01/2024');
        });

        it('same formatter switches output by locale context for datetime mode', function (): void {
            $formatter = new DateFormatter(mode: 'datetime');

            expect($formatter->format('2024-01-15 14:30:00', ['_locale' => 'de']))->toBe('15.01.2024 14:30')
                ->and($formatter->format('2024-01-15 14:30:00', ['_locale' => 'en']))->toBe('01/15/2024 2:30 PM')
                ->and($formatter->format('2024-01-15 14:30:00', ['_locale' => 'fr']))->toBe('15/01/2024 14:30');
        });

        it('same formatter switches output by locale context for time mode', function (): void {
            $formatter = new DateFormatter(mode: 'time');

            expect($formatter->format('2024-01-15 14:30:00', ['_locale' => 'de']))->toBe('14:30')
                ->and($formatter->format('2024-01-15 14:30:00', ['_locale' => 'en']))->toBe('2:30 PM')
                ->and($formatter->format('2024-01-15 14:30:00', ['_locale' => 'fr']))->toBe('14:30');
        });
    });
});
