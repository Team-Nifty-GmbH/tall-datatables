<?php

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\View\ComponentAttributeBag;
use TeamNiftyGmbH\DataTable\Helpers\Icon;

describe('Icon', function (): void {
    describe('construction', function (): void {
        it('creates an Icon instance via make', function (): void {
            $icon = Icon::make('check');

            expect($icon)->toBeInstanceOf(Icon::class);
        });

        it('sets name to lowercase', function (): void {
            $icon = new Icon('ArrowUp');

            expect($icon->name)->toBe('arrowup');
        });

        it('sets name to lowercase via make', function (): void {
            $icon = Icon::make('ArrowDown');

            expect($icon->name)->toBe('arrowdown');
        });

        it('sets variant to lowercase', function (): void {
            $icon = new Icon('check', 'Outline');

            expect($icon->variant)->toBe('outline');
        });

        it('defaults variant to solid', function (): void {
            $icon = Icon::make('check');

            expect($icon->variant)->toBe('solid');
        });

        it('accepts custom variant', function (): void {
            $icon = Icon::make('check', 'outline');

            expect($icon->variant)->toBe('outline');
        });

        it('defaults attributes to empty array', function (): void {
            $icon = Icon::make('check');

            expect($icon->attributes)->toBe([]);
        });

        it('accepts array attributes', function (): void {
            $attributes = ['class' => 'w-5 h-5'];
            $icon = Icon::make('check', 'solid', $attributes);

            expect($icon->attributes)->toBe($attributes);
        });

        it('accepts ComponentAttributeBag attributes', function (): void {
            $bag = new ComponentAttributeBag(['class' => 'text-red-500']);
            $icon = Icon::make('check', 'solid', $bag);

            expect($icon->attributes)->toBeInstanceOf(ComponentAttributeBag::class);
        });
    });

    describe('interfaces', function (): void {
        it('implements Htmlable interface', function (): void {
            $icon = Icon::make('check');

            expect($icon)->toBeInstanceOf(Htmlable::class);
        });

        it('implements Responsable interface', function (): void {
            $icon = Icon::make('check');

            expect($icon)->toBeInstanceOf(Responsable::class);
        });
    });

    describe('getUrl', function (): void {
        it('returns a route with name and variant', function (): void {
            $icon = Icon::make('check', 'outline');
            $url = $icon->getUrl();

            expect($url)
                ->toContain('tall-datatables/icons/check')
                ->toContain('outline');
        });

        it('defaults variant to solid', function (): void {
            $icon = Icon::make('arrow-up');
            $url = $icon->getUrl();

            expect($url)->toContain('tall-datatables/icons/arrow-up');
        });
    });

    describe('rendering', function (): void {
        it('getSvg delegates to getView', function (): void {
            $icon = Icon::make('check');

            expect($icon->getSvg())->toBe($icon->getView());
        });

        it('toHtml delegates to getSvg', function (): void {
            $icon = Icon::make('check');

            expect($icon->toHtml())->toBe($icon->getSvg());
        });

        it('toString renders the icon', function (): void {
            $icon = Icon::make('check');
            $string = (string) $icon;

            expect($string)->toBe($icon->getSvg());
        });

        it('getView renders blade x-icon component', function (): void {
            $icon = Icon::make('check');

            $view = $icon->getView();
            expect($view)->toBeString();
        });
    });

    describe('toResponse', function (): void {
        it('returns an HTTP response with SVG content type', function (): void {
            $icon = Icon::make('check');
            $request = new \Illuminate\Http\Request();
            $response = $icon->toResponse($request);

            expect($response)
                ->toBeInstanceOf(\Illuminate\Http\Response::class)
                ->and($response->headers->get('Content-Type'))->toBe('image/svg+xml; charset=utf-8')
                ->and($response->headers->get('Cache-Control'))->toContain('public');
        });

        it('includes max-age cache header', function (): void {
            $icon = Icon::make('check');
            $request = new \Illuminate\Http\Request();
            $response = $icon->toResponse($request);

            expect($response->headers->get('Cache-Control'))->toContain('max-age=31536000');
        });

        it('response content matches getView output', function (): void {
            $icon = Icon::make('check');
            $request = new \Illuminate\Http\Request();
            $response = $icon->toResponse($request);

            expect($response->getContent())->toBe($icon->getView());
        });
    });
});
