<?php

use TeamNiftyGmbH\DataTable\Formatters\EnumFormatter;
use TeamNiftyGmbH\DataTable\Formatters\FormatterRegistry;

enum TestStatusEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case PendingReview = 'pending_review';
}

describe('EnumFormatter', function (): void {
    it('returns empty string for null', function (): void {
        $formatter = new EnumFormatter();

        expect($formatter->format(null))->toBe('');
    });

    it('translates value using Str::headline without enum class', function (): void {
        $formatter = new EnumFormatter();

        expect($formatter->format('pending_review'))->toBe('Pending Review');
    });

    it('translates value using enum name when enum class is provided', function (): void {
        $formatter = new EnumFormatter(TestStatusEnum::class);

        expect($formatter->format('pending_review'))->toBe('Pending Review');
    });

    it('uses enum case name not backing value for translation', function (): void {
        $formatter = new EnumFormatter(TestStatusEnum::class);

        // TestStatusEnum::Active->name = 'Active', Str::headline('Active') = 'Active'
        expect($formatter->format('active'))->toBe('Active');
    });

    it('escapes HTML entities', function (): void {
        $formatter = new EnumFormatter();

        expect($formatter->format('<b>bold</b>'))->toBe('&lt;B&gt;Bold&lt;/B&gt;');
    });

    it('falls back to Str::headline for unknown enum value', function (): void {
        $formatter = new EnumFormatter(TestStatusEnum::class);

        expect($formatter->format('unknown_value'))->toBe('Unknown Value');
    });
});

describe('FormatterRegistry enum detection', function (): void {
    it('resolves native PHP enum to EnumFormatter', function (): void {
        $registry = new FormatterRegistry();

        expect($registry->resolve(TestStatusEnum::class))->toBeInstanceOf(EnumFormatter::class);
    });

    it('resolves enum string to EnumFormatter via autoDetect', function (): void {
        $registry = new FormatterRegistry();

        expect($registry->resolve('enum'))->toBeInstanceOf(EnumFormatter::class);
    });

    it('isEnum returns true for native PHP enum', function (): void {
        $registry = new FormatterRegistry();

        expect($registry->isEnum(TestStatusEnum::class))->toBeTrue();
    });

    it('isEnum returns false for regular class', function (): void {
        $registry = new FormatterRegistry();

        expect($registry->isEnum(FormatterRegistry::class))->toBeFalse();
    });

    it('isEnum returns true for class with tryFrom and cases methods', function (): void {
        $fakeEnum = new class()
        {
            public static function cases(): array
            {
                return [];
            }

            public static function tryFrom(int|string|null $value): ?object
            {
                return null;
            }
        };

        $registry = new FormatterRegistry();

        expect($registry->isEnum($fakeEnum::class))->toBeTrue();
    });
});
