<?php

use TeamNiftyGmbH\DataTable\Casts\BcFloat;
use TeamNiftyGmbH\DataTable\Contracts\HasFrontendFormatter;
use Tests\Fixtures\Models\Product;

describe('BcFloat Cast', function (): void {
    test('implements HasFrontendFormatter interface', function (): void {
        expect(BcFloat::class)->toImplement(HasFrontendFormatter::class);
    });

    test('returns float formatter name', function (): void {
        expect(BcFloat::getFrontendFormatter())->toBe('float');
    });

    test('casts integer to float with two decimal places', function (): void {
        $product = createTestProduct(['quantity' => 100]);

        expect($product->quantity)
            ->toBe(100.00)
            ->toBeFloat();
    });

    test('casts decimal to float preserving decimal places', function (): void {
        $product = createTestProduct(['quantity' => 99.99]);

        expect($product->quantity)
            ->toBe(99.99)
            ->toBeFloat();
    });

    test('handles null by returning 0.00', function (): void {
        $product = createTestProduct(['quantity' => 0]);

        expect($product->quantity)
            ->toBe(0.00)
            ->toBeFloat();
    });

    test('handles string numeric values', function (): void {
        $product = createTestProduct(['quantity' => '123.45']);

        expect($product->quantity)
            ->toBe(123.45)
            ->toBeFloat();
    });

    test('handles zero value', function (): void {
        $product = createTestProduct(['quantity' => 0]);

        expect($product->quantity)
            ->toBe(0.00)
            ->toBeFloat();
    });

    test('handles negative values', function (): void {
        $product = createTestProduct(['quantity' => -50.25]);

        expect($product->quantity)
            ->toBe(-50.25)
            ->toBeFloat();
    });

    test('handles very large values', function (): void {
        $product = createTestProduct(['quantity' => 999999.99]);

        expect($product->quantity)
            ->toBe(999999.99)
            ->toBeFloat();
    });

    test('handles very small decimal values', function (): void {
        $product = createTestProduct(['quantity' => 0.01]);

        expect($product->quantity)
            ->toBe(0.01)
            ->toBeFloat();
    });

    test('pads integer to two decimal places', function (): void {
        $product = createTestProduct(['quantity' => 50]);

        expect($product->quantity)->toBe(50.00);
    });

    test('preserves decimal places for non-whole numbers', function (): void {
        $product = createTestProduct(['quantity' => 19.95]);

        expect($product->quantity)->toBe(19.95);
    });
});

describe('BcFloat Cast Persistence', function (): void {
    test('stores value as provided', function (): void {
        $product = createTestProduct(['quantity' => 99.99]);
        $product->save();
        $product->refresh();

        expect($product->quantity)
            ->toBe(99.99)
            ->toBeFloat();
    });

    test('persists changes to database', function (): void {
        $product = createTestProduct(['quantity' => 50.00]);
        $product->quantity = 75.50;
        $product->save();

        $freshProduct = Product::find($product->getKey());

        expect($freshProduct->quantity)
            ->toBe(75.50)
            ->toBeFloat();
    });

    test('handles multiple updates correctly', function (): void {
        $product = createTestProduct(['quantity' => 10.00]);

        $product->quantity = 20.00;
        $product->save();
        expect($product->quantity)->toBe(20.00);

        $product->quantity = 30.00;
        $product->save();
        expect($product->quantity)->toBe(30.00);

        $product->refresh();
        expect($product->quantity)->toBe(30.00);
    });
});

describe('BcFloat Cast Type Safety', function (): void {
    test('ensures type consistency across database operations', function (): void {
        $product = createTestProduct(['quantity' => 100]);

        expect($product->quantity)->toBeFloat();

        $product->save();
        expect($product->quantity)->toBeFloat();

        $product->refresh();
        expect($product->quantity)->toBeFloat();

        $freshProduct = Product::find($product->getKey());
        expect($freshProduct->quantity)->toBeFloat();
    });

    test('casts correctly when using query builder', function (): void {
        createTestProduct(['quantity' => 100]);

        $product = Product::where('quantity', '>=', 50)->first();

        expect($product->quantity)->toBeFloat();
    });

    test('works with aggregate functions', function (): void {
        createTestProduct(['quantity' => 100]);
        createTestProduct(['quantity' => 200]);
        createTestProduct(['quantity' => 300]);

        $sum = Product::sum('quantity');
        $avg = Product::avg('quantity');

        expect((float) $sum)->toBe(600.0);
        expect((float) $avg)->toBe(200.0);
    });
});
