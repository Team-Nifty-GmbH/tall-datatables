<?php

use TeamNiftyGmbH\DataTable\Casts\BcFloat;
use Tests\Fixtures\Models\Post;

describe('BcFloat Cast', function (): void {
    it('returns float formatter name', function (): void {
        expect(BcFloat::getFrontendFormatter())->toBe('float');
    });

    it('casts integer to float with two decimal places', function (): void {
        $post = createTestPost(['price' => 100]);

        expect($post->price)
            ->toBe(100.00)
            ->toBeFloat();
    });

    it('casts decimal to float preserving decimal places', function (): void {
        $post = createTestPost(['price' => 99.99]);

        expect($post->price)
            ->toBe(99.99)
            ->toBeFloat();
    });

    it('casts null to 0.00', function (): void {
        $post = createTestPost(['price' => null]);

        expect($post->price)
            ->toBe(0.00)
            ->toBeFloat();
    });

    it('handles string numeric values', function (): void {
        $post = createTestPost(['price' => '123.45']);

        expect($post->price)
            ->toBe(123.45)
            ->toBeFloat();
    });

    it('handles zero value', function (): void {
        $post = createTestPost(['price' => 0]);

        expect($post->price)
            ->toBe(0.00)
            ->toBeFloat();
    });

    it('handles negative values', function (): void {
        $post = createTestPost(['price' => -50.25]);

        expect($post->price)
            ->toBe(-50.25)
            ->toBeFloat();
    });

    it('handles very large values', function (): void {
        $post = createTestPost(['price' => 999999999.99]);

        expect($post->price)
            ->toBe(999999999.99)
            ->toBeFloat();
    });

    it('handles very small decimal values', function (): void {
        $post = createTestPost(['price' => 0.01]);

        expect($post->price)
            ->toBe(0.01)
            ->toBeFloat();
    });

    // Empty string is not supported by BcFloat - it expects numeric values
    // it('handles empty string as zero') - skipped as BcFloat requires numeric input
});

describe('BcFloat Set', function (): void {
    it('stores value as provided', function (): void {
        $post = createTestPost(['price' => 99.99]);
        $post->save();
        $post->refresh();

        expect($post->price)
            ->toBe(99.99)
            ->toBeFloat();
    });

    it('persists changes to database', function (): void {
        $post = createTestPost(['price' => 50.00]);
        $post->price = 75.50;
        $post->save();

        $freshPost = Post::find($post->getKey());

        expect($freshPost->price)
            ->toBe(75.50)
            ->toBeFloat();
    });

    it('handles multiple updates correctly', function (): void {
        $post = createTestPost(['price' => 10.00]);

        $post->price = 20.00;
        $post->save();
        expect($post->price)->toBe(20.00);

        $post->price = 30.00;
        $post->save();
        expect($post->price)->toBe(30.00);

        $post->refresh();
        expect($post->price)->toBe(30.00);
    });
});

describe('BcFloat Type Safety', function (): void {
    it('ensures type consistency across database operations', function (): void {
        $post = createTestPost(['price' => 100]);

        expect($post->price)->toBeFloat();

        $post->save();
        expect($post->price)->toBeFloat();

        $post->refresh();
        expect($post->price)->toBeFloat();

        $freshPost = Post::find($post->getKey());
        expect($freshPost->price)->toBeFloat();
    });

    it('casts correctly when using query builder', function (): void {
        createTestPost(['price' => 100]);

        $post = Post::where('price', '>=', 50)->first();

        expect($post->price)->toBeFloat();
    });
});
