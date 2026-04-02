<?php

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('IconController rendering', function (): void {
    test('renders an icon with default solid variant', function (): void {
        $response = $this->get(route('tall-datatables.icons', ['name' => 'check']));

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toBe('image/svg+xml; charset=utf-8');
    });

    test('renders an icon with solid variant explicitly', function (): void {
        $response = $this->get(route('tall-datatables.icons', ['name' => 'check', 'variant' => 'solid']));

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toBe('image/svg+xml; charset=utf-8');
    });

    test('renders an icon with outline variant', function (): void {
        $response = $this->get(route('tall-datatables.icons', ['name' => 'check', 'variant' => 'outline']));

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toBe('image/svg+xml; charset=utf-8');
    });

    test('renders different icon names', function (): void {
        $response = $this->get(route('tall-datatables.icons', ['name' => 'arrow-up']));

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toBe('image/svg+xml; charset=utf-8');
    });
});

describe('IconController response headers', function (): void {
    test('includes cache-control header with public directive', function (): void {
        $response = $this->get(route('tall-datatables.icons', ['name' => 'check']));

        $response->assertOk();
        expect($response->headers->get('Cache-Control'))->toContain('public');
    });

    test('includes max-age cache header', function (): void {
        $response = $this->get(route('tall-datatables.icons', ['name' => 'check']));

        $response->assertOk();
        expect($response->headers->get('Cache-Control'))->toContain('max-age=31536000');
    });
});

describe('IconController with custom attributes', function (): void {
    test('accepts class attribute', function (): void {
        $response = $this->get(route('tall-datatables.icons', ['name' => 'check']) . '?class=w-5+h-5');

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toBe('image/svg+xml; charset=utf-8');
    });

    test('accepts style attribute', function (): void {
        $response = $this->get(route('tall-datatables.icons', ['name' => 'check']) . '?style=color:red');

        $response->assertOk();
    });

    test('accepts width and height attributes', function (): void {
        $response = $this->get(route('tall-datatables.icons', ['name' => 'check']) . '?width=24&height=24');

        $response->assertOk();
    });
});

describe('IconController route constraints', function (): void {
    test('rejects invalid variant values', function (): void {
        $response = $this->get('/tall-datatables/icons/check/invalid');

        $response->assertNotFound();
    });

    test('accepts solid as valid variant', function (): void {
        $response = $this->get('/tall-datatables/icons/check/solid');

        $response->assertOk();
    });

    test('accepts outline as valid variant', function (): void {
        $response = $this->get('/tall-datatables/icons/check/outline');

        $response->assertOk();
    });
});
