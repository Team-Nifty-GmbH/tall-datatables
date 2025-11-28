<?php

use Illuminate\Contracts\Support\Htmlable;
use TeamNiftyGmbH\DataTable\Htmlables\DataTableButton;

describe('DataTableButton', function (): void {
    it('implements Htmlable interface', function (): void {
        expect(DataTableButton::class)->toImplement(Htmlable::class);
    });

    it('can be created with make method', function (): void {
        $button = DataTableButton::make();

        expect($button)->toBeInstanceOf(DataTableButton::class);
    });

    it('can be created with constructor', function (): void {
        $button = new DataTableButton();

        expect($button)->toBeInstanceOf(DataTableButton::class);
    });

    it('supports fluent api for text', function (): void {
        $button = DataTableButton::make()->text('Click me');

        expect($button->text)->toBe('Click me');
    });

    it('supports fluent api for icon', function (): void {
        $button = DataTableButton::make()->icon('pencil');

        expect($button->icon)->toBe('pencil');
    });

    it('supports fluent api for color', function (): void {
        $button = DataTableButton::make()->color('primary');

        expect($button->color)->toBe('primary');
    });

    it('supports fluent api for size', function (): void {
        $button = DataTableButton::make()->size('lg');

        expect($button->size)->toBe('lg');
    });

    it('supports fluent api for href', function (): void {
        $button = DataTableButton::make()->href('/dashboard');

        expect($button->href)->toBe('/dashboard');
    });

    it('supports fluent api for round', function (): void {
        $button = DataTableButton::make()->round();

        expect($button->round)->toBeTrue();
    });

    it('supports fluent api for square', function (): void {
        $button = DataTableButton::make()->square();

        expect($button->square)->toBeTrue();
    });

    it('supports fluent api for outline', function (): void {
        $button = DataTableButton::make()->outline();

        expect($button->outline)->toBeTrue();
    });

    it('supports fluent api for flat', function (): void {
        $button = DataTableButton::make()->flat();

        expect($button->flat)->toBeTrue();
    });

    it('supports fluent api for circle', function (): void {
        $button = DataTableButton::make()->circle();

        expect($button->circle)->toBeTrue();
    });

    it('supports fluent api for light', function (): void {
        $button = DataTableButton::make()->light();

        expect($button->light)->toBeTrue();
    });

    it('supports fluent api for loading', function (): void {
        $button = DataTableButton::make()->loading('submit');

        expect($button->loading)->toBe('submit');
    });

    it('supports fluent api for delay', function (): void {
        $button = DataTableButton::make()->delay('300');

        expect($button->delay)->toBe('300');
    });

    it('supports fluent api for position', function (): void {
        $button = DataTableButton::make()->position('right');

        expect($button->position)->toBe('right');
    });
});

describe('DataTableButton Attributes', function (): void {
    it('can set attributes array', function (): void {
        $button = DataTableButton::make()->attributes(['data-id' => '123']);

        expect($button->attributes)->toBe(['data-id' => '123']);
    });

    it('can merge attributes', function (): void {
        $button = DataTableButton::make()
            ->attributes(['data-id' => '123'])
            ->mergeAttributes(['data-name' => 'test']);

        expect($button->attributes)
            ->toHaveKey('data-id', '123')
            ->toHaveKey('data-name', 'test');
    });

    it('can set class', function (): void {
        $button = DataTableButton::make()->class('my-button');

        expect($button->attributes['class'])->toBe('my-button');
    });

    it('can set class from array', function (): void {
        $button = DataTableButton::make()->class(['btn', 'btn-primary']);

        expect($button->attributes['class'])->toContain('btn');
    });

    it('can set wire:click', function (): void {
        $button = DataTableButton::make()->wireClick('submit()');

        expect($button->attributes['wire:click'])->toBe('submit()');
    });

    it('can set x-on:click', function (): void {
        $button = DataTableButton::make()->xOnClick('handleClick()');

        expect($button->attributes['x-on:click'])->toBe('handleClick()');
    });
});

describe('DataTableButton Conditional Rendering', function (): void {
    it('renders when condition is true', function (): void {
        $button = DataTableButton::make()
            ->text('Test')
            ->when(true);

        $html = $button->toHtml();

        expect($html)->not->toBeEmpty();
    });

    it('does not render when condition is false', function (): void {
        $button = DataTableButton::make()
            ->text('Test')
            ->when(false);

        $html = $button->toHtml();

        expect($html)->toBe('');
    });

    it('accepts closure for when condition', function (): void {
        $button = DataTableButton::make()
            ->text('Test')
            ->when(fn () => true);

        $html = $button->toHtml();

        expect($html)->not->toBeEmpty();
    });

    it('does not render when closure returns false', function (): void {
        $button = DataTableButton::make()
            ->text('Test')
            ->when(fn () => false);

        $html = $button->toHtml();

        expect($html)->toBe('');
    });
});

describe('DataTableButton Method Chaining', function (): void {
    it('supports full method chaining', function (): void {
        $button = DataTableButton::make()
            ->text('Save')
            ->icon('check')
            ->color('primary')
            ->size('md')
            ->round()
            ->loading('save')
            ->wireClick('save()');

        expect($button->text)->toBe('Save');
        expect($button->icon)->toBe('check');
        expect($button->color)->toBe('primary');
        expect($button->size)->toBe('md');
        expect($button->round)->toBeTrue();
        expect($button->loading)->toBe('save');
        expect($button->attributes['wire:click'])->toBe('save()');
    });

    it('can create with named parameters', function (): void {
        $button = DataTableButton::make(
            text: 'Delete',
            icon: 'trash',
            color: 'red',
            flat: true
        );

        expect($button->text)->toBe('Delete');
        expect($button->icon)->toBe('trash');
        expect($button->color)->toBe('red');
        expect($button->flat)->toBeTrue();
    });
});
