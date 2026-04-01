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

    test('getUrl returns a route with name and variant', function (): void {
        $icon = Icon::make('check', 'outline');
        $url = $icon->getUrl();

        expect($url)
            ->toContain('tall-datatables/icons/check')
            ->toContain('outline');
    });

    test('getUrl defaults variant to solid', function (): void {
        $icon = Icon::make('arrow-up');
        $url = $icon->getUrl();

        expect($url)->toContain('tall-datatables/icons/arrow-up');
    });

    test('getSvg delegates to getView', function (): void {
        $icon = Icon::make('check');

        // getSvg and getView should return the same result
        expect($icon->getSvg())->toBe($icon->getView());
    });

    test('toHtml delegates to getSvg', function (): void {
        $icon = Icon::make('check');

        expect($icon->toHtml())->toBe($icon->getSvg());
    });

    test('toString renders the icon', function (): void {
        $icon = Icon::make('check');
        $string = (string) $icon;

        expect($string)->toBe($icon->getSvg());
    });

    test('toResponse returns an HTTP response with SVG content type', function (): void {
        $icon = Icon::make('check');
        $request = new \Illuminate\Http\Request();
        $response = $icon->toResponse($request);

        expect($response)
            ->toBeInstanceOf(\Illuminate\Http\Response::class)
            ->and($response->headers->get('Content-Type'))->toBe('image/svg+xml; charset=utf-8')
            ->and($response->headers->get('Cache-Control'))->toContain('public');
    });

    test('getView renders blade x-icon component', function (): void {
        $icon = Icon::make('check');

        // The getView method should attempt to render the blade component
        // It may throw or return content depending on whether the icon exists
        $view = $icon->getView();
        expect($view)->toBeString();
    });
});
