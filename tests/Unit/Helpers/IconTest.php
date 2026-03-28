<?php

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\View\ComponentAttributeBag;
use TeamNiftyGmbH\DataTable\Helpers\Icon;

describe('Icon', function (): void {
    test('make creates an Icon instance', function (): void {
        $icon = Icon::make('check');

        expect($icon)->toBeInstanceOf(Icon::class);
    });

    test('constructor sets name to lowercase', function (): void {
        $icon = new Icon('ArrowUp');

        expect($icon->name)->toBe('arrowup');
    });

    test('make sets name to lowercase', function (): void {
        $icon = Icon::make('ArrowDown');

        expect($icon->name)->toBe('arrowdown');
    });

    test('constructor sets variant to lowercase', function (): void {
        $icon = new Icon('check', 'Outline');

        expect($icon->variant)->toBe('outline');
    });

    test('defaults variant to solid', function (): void {
        $icon = Icon::make('check');

        expect($icon->variant)->toBe('solid');
    });

    test('accepts custom variant', function (): void {
        $icon = Icon::make('check', 'outline');

        expect($icon->variant)->toBe('outline');
    });

    test('defaults attributes to empty array', function (): void {
        $icon = Icon::make('check');

        expect($icon->attributes)->toBe([]);
    });

    test('accepts array attributes', function (): void {
        $attributes = ['class' => 'w-5 h-5'];
        $icon = Icon::make('check', 'solid', $attributes);

        expect($icon->attributes)->toBe($attributes);
    });

    test('accepts ComponentAttributeBag attributes', function (): void {
        $bag = new ComponentAttributeBag(['class' => 'text-red-500']);
        $icon = Icon::make('check', 'solid', $bag);

        expect($icon->attributes)->toBeInstanceOf(ComponentAttributeBag::class);
    });

    test('implements Htmlable interface', function (): void {
        $icon = Icon::make('check');

        expect($icon)->toBeInstanceOf(Htmlable::class);
    });

    test('implements Responsable interface', function (): void {
        $icon = Icon::make('check');

        expect($icon)->toBeInstanceOf(Responsable::class);
    });
});
