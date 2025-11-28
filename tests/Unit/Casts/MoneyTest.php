<?php

use TeamNiftyGmbH\DataTable\Casts\Money;
use TeamNiftyGmbH\DataTable\Contracts\HasFrontendFormatter;
use Tests\Fixtures\Models\Product;

describe('Money Cast', function (): void {
    it('implements HasFrontendFormatter interface', function (): void {
        expect(Money::class)->toImplement(HasFrontendFormatter::class);
    });

    it('returns money formatter name', function (): void {
        expect(Money::getFrontendFormatter())->toBe('money');
    });

    it('casts integer to float with two decimal places', function (): void {
        $product = createTestProduct(['price' => 100]);

        expect($product->price)
            ->toBe(100.00)
            ->toBeFloat();
    });

    it('casts decimal to float preserving decimal places', function (): void {
        $product = createTestProduct(['price' => 99.99]);

        expect($product->price)
            ->toBe(99.99)
            ->toBeFloat();
    });

    it('handles null by returning 0.00', function (): void {
        $product = createTestProduct(['price' => 0]);

        expect($product->price)
            ->toBe(0.00)
            ->toBeFloat();
    });

    it('handles string numeric values', function (): void {
        $product = createTestProduct(['price' => '123.45']);

        expect($product->price)
            ->toBe(123.45)
            ->toBeFloat();
    });

    it('handles zero value', function (): void {
        $product = createTestProduct(['price' => 0]);

        expect($product->price)
            ->toBe(0.00)
            ->toBeFloat();
    });

    it('handles negative values', function (): void {
        $product = createTestProduct(['price' => -50.25]);

        expect($product->price)
            ->toBe(-50.25)
            ->toBeFloat();
    });

    it('handles very large values', function (): void {
        $product = createTestProduct(['price' => 999999.99]);

        expect($product->price)
            ->toBe(999999.99)
            ->toBeFloat();
    });

    it('handles very small decimal values', function (): void {
        $product = createTestProduct(['price' => 0.01]);

        expect($product->price)
            ->toBe(0.01)
            ->toBeFloat();
    });

    it('pads integer to two decimal places', function (): void {
        $product = createTestProduct(['price' => 50]);

        expect($product->price)->toBe(50.00);
    });

    it('preserves decimal places for non-whole numbers', function (): void {
        $product = createTestProduct(['price' => 19.95]);

        expect($product->price)->toBe(19.95);
    });
});

describe('Money Cast Persistence', function (): void {
    it('stores value as provided', function (): void {
        $product = createTestProduct(['price' => 99.99]);
        $product->save();
        $product->refresh();

        expect($product->price)
            ->toBe(99.99)
            ->toBeFloat();
    });

    it('persists changes to database', function (): void {
        $product = createTestProduct(['price' => 50.00]);
        $product->price = 75.50;
        $product->save();

        $freshProduct = Product::find($product->getKey());

        expect($freshProduct->price)
            ->toBe(75.50)
            ->toBeFloat();
    });

    it('handles multiple updates correctly', function (): void {
        $product = createTestProduct(['price' => 10.00]);

        $product->price = 20.00;
        $product->save();
        expect($product->price)->toBe(20.00);

        $product->price = 30.00;
        $product->save();
        expect($product->price)->toBe(30.00);

        $product->refresh();
        expect($product->price)->toBe(30.00);
    });
});

describe('Money Cast Type Safety', function (): void {
    it('ensures type consistency across database operations', function (): void {
        $product = createTestProduct(['price' => 100]);

        expect($product->price)->toBeFloat();

        $product->save();
        expect($product->price)->toBeFloat();

        $product->refresh();
        expect($product->price)->toBeFloat();

        $freshProduct = Product::find($product->getKey());
        expect($freshProduct->price)->toBeFloat();
    });

    it('casts correctly when using query builder', function (): void {
        createTestProduct(['price' => 100]);

        $product = Product::where('price', '>=', 50)->first();

        expect($product->price)->toBeFloat();
    });

    it('works with aggregate functions', function (): void {
        createTestProduct(['price' => 100]);
        createTestProduct(['price' => 200]);
        createTestProduct(['price' => 300]);

        $sum = Product::sum('price');
        $avg = Product::avg('price');

        expect((float) $sum)->toBe(600.0);
        expect((float) $avg)->toBe(200.0);
    });
});
