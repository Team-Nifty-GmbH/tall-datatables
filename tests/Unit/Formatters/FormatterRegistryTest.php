<?php

use Illuminate\Contracts\Support\Arrayable;
use TeamNiftyGmbH\DataTable\Formatters\ArrayFormatter;
use TeamNiftyGmbH\DataTable\Formatters\BadgeFormatter;
use TeamNiftyGmbH\DataTable\Formatters\BooleanFormatter;
use TeamNiftyGmbH\DataTable\Formatters\DateFormatter;
use TeamNiftyGmbH\DataTable\Formatters\FloatFormatter;
use TeamNiftyGmbH\DataTable\Formatters\FormatterRegistry;
use TeamNiftyGmbH\DataTable\Formatters\ImageFormatter;
use TeamNiftyGmbH\DataTable\Formatters\LinkFormatter;
use TeamNiftyGmbH\DataTable\Formatters\MoneyFormatter;
use TeamNiftyGmbH\DataTable\Formatters\PercentageFormatter;
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

    it('auto-detects image cast to ImageFormatter', function (): void {
        $registry = new FormatterRegistry();

        expect($registry->resolve('image'))->toBeInstanceOf(ImageFormatter::class);
    });

    it('auto-detects array cast to ArrayFormatter', function (): void {
        $registry = new FormatterRegistry();

        expect($registry->resolve('array'))->toBeInstanceOf(ArrayFormatter::class);
    });

    it('auto-detects json cast to ArrayFormatter', function (): void {
        $registry = new FormatterRegistry();

        expect($registry->resolve('json'))->toBeInstanceOf(ArrayFormatter::class);
    });

    it('auto-detects collection cast to ArrayFormatter', function (): void {
        $registry = new FormatterRegistry();

        expect($registry->resolve('collection'))->toBeInstanceOf(ArrayFormatter::class);
    });

    it('auto-detects state cast to BadgeFormatter', function (): void {
        $registry = new FormatterRegistry();

        expect($registry->resolve('state'))->toBeInstanceOf(BadgeFormatter::class);
    });

    it('auto-detects badge cast to BadgeFormatter', function (): void {
        $registry = new FormatterRegistry();

        expect($registry->resolve('badge'))->toBeInstanceOf(BadgeFormatter::class);
    });

    it('resolves state with options to BadgeFormatter with mapping', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolveWithOptions('state', ['open' => 'green', 'closed' => 'red']);

        expect($formatter)->toBeInstanceOf(BadgeFormatter::class)
            ->and($formatter->mapping)->toHaveKey('open')
            ->and($formatter->mapping['open']['color'])->toBe('green')
            ->and($formatter->mapping['closed']['color'])->toBe('red');
    });

    it('resolves badge with nested options array', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolveWithOptions('badge', [['active' => 'blue', 'inactive' => 'gray']]);

        expect($formatter)->toBeInstanceOf(BadgeFormatter::class)
            ->and($formatter->mapping)->toHaveKey('active')
            ->and($formatter->mapping['active']['color'])->toBe('blue');
    });

    it('resolves FQCN cast by stripping namespace to basename', function (): void {
        $registry = new FormatterRegistry();

        $registry->register('Money', new MoneyFormatter());

        $formatter = $registry->resolve('FluxErp\\Casts\\Money');

        expect($formatter)->toBeInstanceOf(MoneyFormatter::class);
    });

    it('auto-detects FQCN cast basename when not registered', function (): void {
        $registry = new FormatterRegistry();

        $formatter = $registry->resolve('App\\Casts\\Money');

        expect($formatter)->toBeInstanceOf(MoneyFormatter::class);
    });

    it('falls back to StringFormatter for unknown FQCN', function (): void {
        $registry = new FormatterRegistry();

        $formatter = $registry->resolve('App\\Casts\\SomethingCompletelyUnknown');

        expect($formatter)->toBeInstanceOf(StringFormatter::class);
    });

    it('auto-detects money cast to MoneyFormatter', function (): void {
        $registry = new FormatterRegistry();

        expect($registry->resolve('money'))->toBeInstanceOf(MoneyFormatter::class);
    });

    it('auto-detects coloredmoney cast to colored MoneyFormatter', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolve('coloredmoney');

        expect($formatter)->toBeInstanceOf(MoneyFormatter::class)
            ->and($formatter->colored)->toBeTrue();
    });

    it('auto-detects percentage cast to PercentageFormatter', function (): void {
        $registry = new FormatterRegistry();

        expect($registry->resolve('percentage'))->toBeInstanceOf(PercentageFormatter::class);
    });

    it('auto-detects progresspercentage cast to PercentageFormatter with progressBar', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolve('progresspercentage');

        expect($formatter)->toBeInstanceOf(PercentageFormatter::class)
            ->and($formatter->progressBar)->toBeTrue();
    });

    it('auto-detects coloredfloat cast to colored FloatFormatter', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolve('coloredfloat');

        expect($formatter)->toBeInstanceOf(FloatFormatter::class)
            ->and($formatter->colored)->toBeTrue();
    });

    it('auto-detects email cast to LinkFormatter with email type', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolve('email');

        expect($formatter)->toBeInstanceOf(LinkFormatter::class)
            ->and($formatter->type)->toBe('email');
    });

    it('auto-detects tel cast to LinkFormatter with tel type', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolve('tel');

        expect($formatter)->toBeInstanceOf(LinkFormatter::class)
            ->and($formatter->type)->toBe('tel');
    });

    it('auto-detects url cast to LinkFormatter', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolve('url');

        expect($formatter)->toBeInstanceOf(LinkFormatter::class)
            ->and($formatter->type)->toBe('link');
    });

    it('auto-detects link cast to LinkFormatter', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolve('link');

        expect($formatter)->toBeInstanceOf(LinkFormatter::class)
            ->and($formatter->type)->toBe('link');
    });

    it('auto-detects relativetime cast to DateFormatter with relative mode', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolve('relativetime');

        expect($formatter)->toBeInstanceOf(DateFormatter::class)
            ->and($formatter->mode)->toBe('relative');
    });

    it('auto-detects time cast to DateFormatter with time mode', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolve('time');

        expect($formatter)->toBeInstanceOf(DateFormatter::class)
            ->and($formatter->mode)->toBe('time');
    });

    it('auto-detects float cast to FloatFormatter', function (): void {
        $registry = new FormatterRegistry();

        expect($registry->resolve('float'))->toBeInstanceOf(FloatFormatter::class);
    });

    it('auto-detects double cast to FloatFormatter', function (): void {
        $registry = new FormatterRegistry();

        expect($registry->resolve('double'))->toBeInstanceOf(FloatFormatter::class);
    });

    it('auto-detects decimal cast to FloatFormatter', function (): void {
        $registry = new FormatterRegistry();

        expect($registry->resolve('decimal'))->toBeInstanceOf(FloatFormatter::class);
    });

    it('auto-detects integer cast to StringFormatter', function (): void {
        $registry = new FormatterRegistry();

        expect($registry->resolve('integer'))->toBeInstanceOf(StringFormatter::class);
    });

    it('auto-detects int cast to StringFormatter', function (): void {
        $registry = new FormatterRegistry();

        expect($registry->resolve('int'))->toBeInstanceOf(StringFormatter::class);
    });

    it('resolveWithOptions falls through to resolve for non-state/badge types', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolveWithOptions('boolean', ['some' => 'options']);

        expect($formatter)->toBeInstanceOf(BooleanFormatter::class);
    });

    it('resolveWithOptions falls through for unknown cast type', function (): void {
        $registry = new FormatterRegistry();
        $formatter = $registry->resolveWithOptions('unknown', []);

        expect($formatter)->toBeInstanceOf(StringFormatter::class);
    });

    it('resolveWithOptions is case-insensitive for state/badge', function (): void {
        $registry = new FormatterRegistry();

        $formatter = $registry->resolveWithOptions('State', ['open' => 'green']);

        expect($formatter)->toBeInstanceOf(BadgeFormatter::class)
            ->and($formatter->mapping['open']['color'])->toBe('green');
    });

    it('resolveWithOptions handles Arrayable options', function (): void {
        $registry = new FormatterRegistry();

        $arrayable = new class implements Arrayable
        {
            public function toArray(): array
            {
                return ['pending' => 'yellow', 'done' => 'green'];
            }
        };

        $formatter = $registry->resolveWithOptions('badge', $arrayable);

        expect($formatter)->toBeInstanceOf(BadgeFormatter::class)
            ->and($formatter->mapping)->toHaveKey('pending')
            ->and($formatter->mapping['pending']['color'])->toBe('yellow')
            ->and($formatter->mapping['done']['color'])->toBe('green');
    });

    it('resolveWithOptions translates labels in mapping', function (): void {
        $registry = new FormatterRegistry();

        $formatter = $registry->resolveWithOptions('state', ['active' => 'green']);

        expect($formatter)->toBeInstanceOf(BadgeFormatter::class)
            ->and($formatter->mapping['active'])->toHaveKey('label');
    });

    it('resolves registered FQCN formatter directly', function (): void {
        $registry = new FormatterRegistry();
        $custom = new StringFormatter();

        $registry->register('App\\Casts\\CustomType', $custom);

        expect($registry->resolve('App\\Casts\\CustomType'))->toBe($custom);
    });

    it('resolveWithOptions with Arrayable for non-state type falls through', function (): void {
        $registry = new FormatterRegistry();

        $arrayable = new class implements Arrayable
        {
            public function toArray(): array
            {
                return ['key' => 'value'];
            }
        };

        $formatter = $registry->resolveWithOptions('date', $arrayable);

        expect($formatter)->toBeInstanceOf(DateFormatter::class);
    });
});
