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

    it('renders all color variants correctly', function (string $color, string $bgClass, string $textClass): void {
        $formatter = new BadgeFormatter(mapping: [
            'status' => ['color' => $color, 'label' => 'Status'],
        ]);

        $result = $formatter->format('status');

        expect($result)
            ->toContain($bgClass)
            ->toContain($textClass)
            ->toContain('Status');
    })->with([
        'red' => ['red', 'bg-red-100', 'text-red-800'],
        'orange' => ['orange', 'bg-orange-100', 'text-orange-800'],
        'amber' => ['amber', 'bg-amber-100', 'text-amber-800'],
        'yellow' => ['yellow', 'bg-yellow-100', 'text-yellow-800'],
        'lime' => ['lime', 'bg-lime-100', 'text-lime-800'],
        'green' => ['green', 'bg-green-100', 'text-green-800'],
        'emerald' => ['emerald', 'bg-emerald-100', 'text-emerald-800'],
        'teal' => ['teal', 'bg-teal-100', 'text-teal-800'],
        'cyan' => ['cyan', 'bg-cyan-100', 'text-cyan-800'],
        'sky' => ['sky', 'bg-sky-100', 'text-sky-800'],
        'blue' => ['blue', 'bg-blue-100', 'text-blue-800'],
        'indigo' => ['indigo', 'bg-indigo-100', 'text-indigo-800'],
        'violet' => ['violet', 'bg-violet-100', 'text-violet-800'],
        'purple' => ['purple', 'bg-purple-100', 'text-purple-800'],
        'fuchsia' => ['fuchsia', 'bg-fuchsia-100', 'text-fuchsia-800'],
        'pink' => ['pink', 'bg-pink-100', 'text-pink-800'],
        'rose' => ['rose', 'bg-rose-100', 'text-rose-800'],
    ]);

    it('falls back to gray for unknown color', function (): void {
        $formatter = new BadgeFormatter(mapping: [
            'status' => ['color' => 'nonexistent-color', 'label' => 'Status'],
        ]);

        $result = $formatter->format('status');

        expect($result)
            ->toContain('bg-gray-100')
            ->toContain('text-gray-800');
    });

    it('formats integer values as badge', function (): void {
        $formatter = new BadgeFormatter(mapping: [
            '42' => ['color' => 'blue', 'label' => 'Answer'],
        ]);

        $result = $formatter->format(42);

        expect($result)
            ->toContain('bg-blue-100')
            ->toContain('Answer');
    });

    it('formats float values as badge', function (): void {
        $formatter = new BadgeFormatter(mapping: [
            '3.14' => ['color' => 'green', 'label' => 'Pi'],
        ]);

        $result = $formatter->format(3.14);

        expect($result)
            ->toContain('bg-green-100')
            ->toContain('Pi');
    });

    it('formats boolean true as badge', function (): void {
        $formatter = new BadgeFormatter(mapping: [
            '1' => ['color' => 'green', 'label' => 'Yes'],
        ]);

        $result = $formatter->format(true);

        expect($result)
            ->toContain('bg-green-100')
            ->toContain('Yes');
    });

    it('formats boolean false as badge', function (): void {
        $formatter = new BadgeFormatter();

        $result = $formatter->format(false);

        expect($result)
            ->toContain('bg-gray-100')
            ->toContain('<span');
    });

    it('formats empty string as gray badge', function (): void {
        $formatter = new BadgeFormatter();

        $result = $formatter->format('');

        expect($result)
            ->toContain('bg-gray-100')
            ->toContain('text-gray-800')
            ->toContain('<span');
    });

    it('renders unmapped integer value with gray fallback', function (): void {
        $formatter = new BadgeFormatter(mapping: [
            'other' => ['color' => 'blue', 'label' => 'Other'],
        ]);

        $result = $formatter->format(99);

        expect($result)
            ->toContain('bg-gray-100')
            ->toContain('99');
    });

    it('renders full badge HTML structure', function (): void {
        $formatter = new BadgeFormatter(mapping: [
            'done' => ['color' => 'emerald', 'label' => 'Done'],
        ]);

        $result = $formatter->format('done');

        expect($result)->toBe(
            '<span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-emerald-100 text-emerald-800">Done</span>'
        );
    });

    it('uses default gray color when mapping has no color key', function (): void {
        $formatter = new BadgeFormatter(mapping: [
            'pending' => ['label' => 'Pending'],
        ]);

        $result = $formatter->format('pending');

        expect($result)
            ->toContain('bg-gray-100')
            ->toContain('text-gray-800')
            ->toContain('Pending');
    });
});
