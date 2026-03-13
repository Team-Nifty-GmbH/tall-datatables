<?php

use TeamNiftyGmbH\DataTable\Formatters\BadgeFormatter;

describe('BadgeFormatter', function (): void {
    it('returns empty string for null', function (): void {
        $formatter = new BadgeFormatter();

        expect($formatter->format(null))->toBe('');
    });

    it('renders a mapped value with correct color', function (): void {
        $formatter = new BadgeFormatter(mapping: [
            'active' => ['color' => 'green', 'label' => 'Active'],
        ]);

        $result = $formatter->format('active');

        expect($result)
            ->toContain('bg-green-100')
            ->toContain('text-green-800')
            ->toContain('Active');
    });

    it('renders unknown value with gray badge', function (): void {
        $formatter = new BadgeFormatter();
        $result = $formatter->format('unknown-status');

        expect($result)
            ->toContain('bg-gray-100')
            ->toContain('text-gray-800')
            ->toContain('unknown-status');
    });

    it('uses raw value as label when no label in mapping', function (): void {
        $formatter = new BadgeFormatter(mapping: [
            'active' => ['color' => 'green'],
        ]);

        $result = $formatter->format('active');

        expect($result)->toContain('active');
    });

    it('escapes XSS in value', function (): void {
        $formatter = new BadgeFormatter();
        $result = $formatter->format('<script>alert(1)</script>');

        expect($result)
            ->not->toContain('<script>')
            ->toContain('&lt;script&gt;');
    });

    it('escapes XSS in mapped label', function (): void {
        $formatter = new BadgeFormatter(mapping: [
            'xss' => ['color' => 'red', 'label' => '<script>evil()</script>'],
        ]);

        $result = $formatter->format('xss');

        expect($result)->not->toContain('<script>');
    });

    it('renders span with badge classes', function (): void {
        $formatter = new BadgeFormatter();
        $result = $formatter->format('test');

        expect($result)
            ->toContain('<span')
            ->toContain('inline-flex')
            ->toContain('rounded-full')
            ->toContain('text-xs');
    });

    it('renders different colors correctly', function (): void {
        $formatter = new BadgeFormatter(mapping: [
            'error' => ['color' => 'red', 'label' => 'Error'],
            'info' => ['color' => 'blue', 'label' => 'Info'],
        ]);

        expect($formatter->format('error'))->toContain('bg-red-100');
        expect($formatter->format('info'))->toContain('bg-blue-100');
    });
});
