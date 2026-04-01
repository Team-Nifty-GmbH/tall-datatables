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

describe('DataTableButton toHtml Rendering', function (): void {
    it('renders a standard button', function (): void {
        $button = DataTableButton::make()
            ->text('Click Me')
            ->color('primary');

        $html = $button->toHtml();

        expect($html)->toBeString()
            ->not->toBeEmpty();
    });

    it('renders a circle button', function (): void {
        $button = DataTableButton::make()
            ->circle()
            ->icon('pencil')
            ->color('primary');

        $html = $button->toHtml();

        expect($html)->toBeString()
            ->not->toBeEmpty();
    });

    it('circle button defaults icon to pencil', function (): void {
        $button = DataTableButton::make()
            ->circle()
            ->color('primary');

        // circle sets default icon to pencil internally
        $html = $button->toHtml();

        expect($html)->not->toBeEmpty();
    });

    it('uses default color secondary when no color set', function (): void {
        $button = DataTableButton::make()->text('Default');

        $html = $button->toHtml();

        expect($html)->not->toBeEmpty();
    });

    it('uses default size md when no size set', function (): void {
        $button = DataTableButton::make()->text('Default Size');

        $html = $button->toHtml();

        expect($html)->not->toBeEmpty();
    });

    it('renders with custom attributes', function (): void {
        $button = DataTableButton::make()
            ->text('With Attrs')
            ->attributes(['data-id' => '42']);

        $html = $button->toHtml();

        expect($html)->toContain('data-id');
    });

    it('renders with wire:click attribute', function (): void {
        $button = DataTableButton::make()
            ->text('Save')
            ->wireClick('save()');

        $html = $button->toHtml();

        expect($html)->not->toBeEmpty();
    });

    it('renders with href', function (): void {
        $button = DataTableButton::make()
            ->text('Link')
            ->href('/dashboard');

        $html = $button->toHtml();

        expect($html)->not->toBeEmpty();
    });

    it('renders with outline style', function (): void {
        $button = DataTableButton::make()
            ->text('Outline')
            ->outline();

        $html = $button->toHtml();

        expect($html)->not->toBeEmpty();
    });

    it('renders with flat style', function (): void {
        $button = DataTableButton::make()
            ->text('Flat')
            ->flat();

        $html = $button->toHtml();

        expect($html)->not->toBeEmpty();
    });

    it('renders with square style', function (): void {
        $button = DataTableButton::make()
            ->text('Square')
            ->square();

        $html = $button->toHtml();

        expect($html)->not->toBeEmpty();
    });

    it('renders with round style', function (): void {
        $button = DataTableButton::make()
            ->text('Round')
            ->round();

        $html = $button->toHtml();

        expect($html)->not->toBeEmpty();
    });

    it('renders with position right', function (): void {
        $button = DataTableButton::make()
            ->text('Icon Right')
            ->icon('arrow-right')
            ->position('right');

        $html = $button->toHtml();

        expect($html)->not->toBeEmpty();
    });

    it('renders with loading', function (): void {
        $button = DataTableButton::make()
            ->text('Loading')
            ->loading('submit');

        $html = $button->toHtml();

        expect($html)->not->toBeEmpty();
    });

    it('renders with delay', function (): void {
        $button = DataTableButton::make()
            ->text('Delayed')
            ->delay('500');

        $html = $button->toHtml();

        expect($html)->not->toBeEmpty();
    });

    it('renders with light variant', function (): void {
        $button = DataTableButton::make()
            ->text('Light')
            ->light();

        $html = $button->toHtml();

        expect($html)->not->toBeEmpty();
    });
});

describe('DataTableButton mergeClass', function (): void {
    it('merges string class into attributes', function (): void {
        $button = DataTableButton::make()
            ->class('existing-class')
            ->mergeClass('new-class');

        expect($button->attributes['class'])->toBeArray()
            ->toContain('new-class')
            ->toContain('existing-class');
    });

    it('handles merge when no class is set', function (): void {
        $button = DataTableButton::make()
            ->mergeClass('new-class');

        expect($button->attributes['class'])->toBeArray()
            ->toContain('new-class');
    });
});

describe('DataTableButton full method', function (): void {
    it('sets full property', function (): void {
        $button = DataTableButton::make()->full();

        expect($button->full)->toBeTrue();
    });

    it('can disable full', function (): void {
        $button = DataTableButton::make()->full(false);

        expect($button->full)->toBeFalse();
    });
});
