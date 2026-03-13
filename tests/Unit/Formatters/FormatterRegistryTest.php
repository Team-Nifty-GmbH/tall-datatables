<?php

use TeamNiftyGmbH\DataTable\Formatters\BooleanFormatter;
use TeamNiftyGmbH\DataTable\Formatters\DateFormatter;
use TeamNiftyGmbH\DataTable\Formatters\FloatFormatter;
use TeamNiftyGmbH\DataTable\Formatters\FormatterRegistry;
use TeamNiftyGmbH\DataTable\Formatters\StringFormatter;

describe('FormatterRegistry', function (): void {
    it('falls back to StringFormatter for unknown cast', function (): void {
        $registry = new FormatterRegistry();

        expect($registry->resolve('unknown'))->toBeInstanceOf(StringFormatter::class);
    });

    it('auto-detects boolean cast to BooleanFormatter', function (): void {
        $registry = new FormatterRegistry();

        expect($registry->resolve('boolean'))->toBeInstanceOf(BooleanFormatter::class);
    });

    it('auto-detects bool cast to BooleanFormatter', function (): void {
        $registry = new FormatterRegistry();

        expect($registry->resolve('bool'))->toBeInstanceOf(BooleanFormatter::class);
    });

    it('auto-detects date cast to DateFormatter with date mode', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolve('date');

        expect($formatter)->toBeInstanceOf(DateFormatter::class)
            ->and($formatter->mode)->toBe('date');
    });

    it('auto-detects immutable_date cast to DateFormatter with date mode', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolve('immutable_date');

        expect($formatter)->toBeInstanceOf(DateFormatter::class)
            ->and($formatter->mode)->toBe('date');
    });

    it('auto-detects datetime cast to DateFormatter with datetime mode', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolve('datetime');

        expect($formatter)->toBeInstanceOf(DateFormatter::class)
            ->and($formatter->mode)->toBe('datetime');
    });

    it('auto-detects immutable_datetime cast to DateFormatter with datetime mode', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolve('immutable_datetime');

        expect($formatter)->toBeInstanceOf(DateFormatter::class)
            ->and($formatter->mode)->toBe('datetime');
    });

    it('auto-detects timestamp cast to DateFormatter with datetime mode', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolve('timestamp');

        expect($formatter)->toBeInstanceOf(DateFormatter::class)
            ->and($formatter->mode)->toBe('datetime');
    });

    it('registers and resolves a custom formatter', function (): void {
        $registry = new FormatterRegistry();
        $floatFormatter = new FloatFormatter();

        $registry->register('custom_cast', $floatFormatter);

        expect($registry->resolve('custom_cast'))->toBe($floatFormatter);
    });

    it('custom registered formatter overrides auto-detection', function (): void {
        $registry = new FormatterRegistry();
        $floatFormatter = new FloatFormatter();

        $registry->register('boolean', $floatFormatter);

        expect($registry->resolve('boolean'))->toBe($floatFormatter);
    });

    it('register returns static for fluent chaining', function (): void {
        $registry = new FormatterRegistry();

        expect($registry->register('test', new StringFormatter()))->toBe($registry);
    });

    it('resolves formatter for column with cast', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolveForColumn('is_active', ['is_active' => 'boolean']);

        expect($formatter)->toBeInstanceOf(BooleanFormatter::class);
    });

    it('falls back to StringFormatter for column without cast', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolveForColumn('name', []);

        expect($formatter)->toBeInstanceOf(StringFormatter::class);
    });

    it('handles cast with params like date:Y-m-d', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolveForColumn('created_at', ['created_at' => 'date:Y-m-d']);

        expect($formatter)->toBeInstanceOf(DateFormatter::class)
            ->and($formatter->mode)->toBe('date');
    });

    it('handles datetime cast with params', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolveForColumn('updated_at', ['updated_at' => 'datetime:Y-m-d H:i:s']);

        expect($formatter)->toBeInstanceOf(DateFormatter::class)
            ->and($formatter->mode)->toBe('datetime');
    });
});
