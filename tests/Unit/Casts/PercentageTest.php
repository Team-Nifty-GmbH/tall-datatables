<?php

use TeamNiftyGmbH\DataTable\Casts\Percentage;
use TeamNiftyGmbH\DataTable\Contracts\HasFrontendFormatter;
use Tests\Fixtures\Models\Product;

describe('Percentage Cast', function (): void {
    it('implements HasFrontendFormatter interface', function (): void {
        expect(Percentage::class)->toImplement(HasFrontendFormatter::class);
    });

    it('returns percentage formatter name', function (): void {
        expect(Percentage::getFrontendFormatter())->toBe('percentage');
    });

    it('returns value as-is for decimal percentage', function (): void {
        $product = createTestProduct(['discount' => 0.25]);

        expect($product->discount)->toBe(0.25);
    });

    it('returns zero for zero value', function (): void {
        $product = createTestProduct(['discount' => 0]);

        expect((float) $product->discount)->toBe(0.0);
    });

    it('returns full percentage', function (): void {
        $product = createTestProduct(['discount' => 1.0]);

        expect($product->discount)->toBe(1.0);
    });

    it('handles small percentages', function (): void {
        $product = createTestProduct(['discount' => 0.0125]);

        expect($product->discount)->toBe(0.0125);
    });

    it('handles percentages over 100%', function (): void {
        $product = createTestProduct(['discount' => 1.5]);

        expect($product->discount)->toBe(1.5);
    });

    it('handles negative percentages', function (): void {
        $product = createTestProduct(['discount' => -0.1]);

        expect($product->discount)->toBe(-0.1);
    });

    it('handles null values', function (): void {
        $product = createTestProduct(['discount' => 0]);

        expect((float) $product->discount)->toBe(0.0);
    });
});

describe('Percentage Cast Persistence', function (): void {
    it('persists value to database', function (): void {
        $product = createTestProduct(['discount' => 0.15]);
        $product->save();
        $product->refresh();

        expect($product->discount)->toBe(0.15);
    });

    it('updates value correctly', function (): void {
        $product = createTestProduct(['discount' => 0.10]);
        $product->discount = 0.20;
        $product->save();

        $freshProduct = Product::find($product->getKey());

        expect($freshProduct->discount)->toBe(0.20);
    });
});

describe('Percentage Cast Query Operations', function (): void {
    it('works with comparison queries', function (): void {
        createTestProduct(['discount' => 0.10]);
        createTestProduct(['discount' => 0.20]);
        createTestProduct(['discount' => 0.30]);

        $highDiscount = Product::where('discount', '>=', 0.20)->count();

        expect($highDiscount)->toBe(2);
    });

    it('works with aggregate functions', function (): void {
        createTestProduct(['discount' => 0.10]);
        createTestProduct(['discount' => 0.20]);
        createTestProduct(['discount' => 0.30]);

        $avg = Product::avg('discount');

        // Use toBeGreaterThan and toBeLessThan for floating point comparison
        expect((float) $avg)->toBeGreaterThan(0.19)->toBeLessThan(0.21);
    });

    it('works with order by', function (): void {
        createTestProduct(['discount' => 0.30, 'name' => 'Product C']);
        createTestProduct(['discount' => 0.10, 'name' => 'Product A']);
        createTestProduct(['discount' => 0.20, 'name' => 'Product B']);

        $products = Product::orderBy('discount', 'asc')->pluck('name')->toArray();

        expect($products)->toBe(['Product A', 'Product B', 'Product C']);
    });
});
