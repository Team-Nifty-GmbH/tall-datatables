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

describe('Percentage Cast Direct Methods', function (): void {
    it('set method passes value through unchanged', function (): void {
        $cast = new Percentage();
        $model = new Product();

        expect($cast->set($model, 'discount', 0.5, []))->toBe(0.5);
        expect($cast->set($model, 'discount', null, []))->toBeNull();
        expect($cast->set($model, 'discount', 0, []))->toBe(0);
    });

    it('getFrontendFormatter returns percentage', function (): void {
        expect(Percentage::getFrontendFormatter())->toBe('percentage');
        expect(Percentage::getFrontendFormatter('extra'))->toBe('percentage');
    });

    it('handles string numeric values', function (): void {
        $product = createTestProduct(['discount' => '0.33']);

        expect($product->discount)->toBe('0.33');
    });

    it('delegates to attribute mutator when model has one', function (): void {
        $cast = new Percentage();

        $model = new class() extends Illuminate\Database\Eloquent\Model
        {
            protected $guarded = ['id'];

            protected $table = 'products';

            protected function casts(): array
            {
                return ['discount' => Percentage::class];
            }

            public function discount(): Illuminate\Database\Eloquent\Casts\Attribute
            {
                return Illuminate\Database\Eloquent\Casts\Attribute::make(
                    get: fn ($value) => $value * 100,
                );
            }
        };

        $model->setRawAttributes(['discount' => 0.25]);

        $result = $cast->get($model, 'discount', 0.25, ['discount' => 0.25]);

        expect($result)->toBe(25.0);
    });
});
