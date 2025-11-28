<?php

use TeamNiftyGmbH\DataTable\Casts\Links\Image;
use TeamNiftyGmbH\DataTable\Contracts\HasFrontendFormatter;
use Tests\Fixtures\Models\Product;

describe('Image Cast', function (): void {
    it('implements HasFrontendFormatter interface', function (): void {
        expect(Image::class)->toImplement(HasFrontendFormatter::class);
    });

    it('returns image formatter name', function (): void {
        expect(Image::getFrontendFormatter())->toBe('image');
    });

    it('returns image url value as-is', function (): void {
        $url = 'https://example.com/image.jpg';
        $product = createTestProduct(['image_url' => $url]);

        expect($product->image_url)->toBe($url);
    });

    it('handles null values', function (): void {
        $product = createTestProduct(['image_url' => null]);

        expect($product->image_url)->toBeNull();
    });

    it('handles empty string', function (): void {
        $product = createTestProduct(['image_url' => '']);

        expect($product->image_url)->toBe('');
    });

    it('handles jpg images', function (): void {
        $url = 'https://example.com/photo.jpg';
        $product = createTestProduct(['image_url' => $url]);

        expect($product->image_url)->toBe($url);
    });

    it('handles png images', function (): void {
        $url = 'https://example.com/logo.png';
        $product = createTestProduct(['image_url' => $url]);

        expect($product->image_url)->toBe($url);
    });

    it('handles gif images', function (): void {
        $url = 'https://example.com/animation.gif';
        $product = createTestProduct(['image_url' => $url]);

        expect($product->image_url)->toBe($url);
    });

    it('handles webp images', function (): void {
        $url = 'https://example.com/photo.webp';
        $product = createTestProduct(['image_url' => $url]);

        expect($product->image_url)->toBe($url);
    });

    it('handles svg images', function (): void {
        $url = 'https://example.com/icon.svg';
        $product = createTestProduct(['image_url' => $url]);

        expect($product->image_url)->toBe($url);
    });

    it('handles cdn urls', function (): void {
        $url = 'https://cdn.cloudflare.com/images/product-123.jpg';
        $product = createTestProduct(['image_url' => $url]);

        expect($product->image_url)->toBe($url);
    });

    it('handles s3 urls', function (): void {
        $url = 'https://bucket.s3.amazonaws.com/images/product.png';
        $product = createTestProduct(['image_url' => $url]);

        expect($product->image_url)->toBe($url);
    });

    it('handles data urls', function (): void {
        $url = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $product = createTestProduct(['image_url' => $url]);

        expect($product->image_url)->toBe($url);
    });

    it('handles urls with query parameters', function (): void {
        $url = 'https://example.com/image.jpg?width=800&height=600';
        $product = createTestProduct(['image_url' => $url]);

        expect($product->image_url)->toBe($url);
    });
});

describe('Image Cast Persistence', function (): void {
    it('persists value to database', function (): void {
        $url = 'https://example.com/image.jpg';
        $product = createTestProduct(['image_url' => $url]);
        $product->save();
        $product->refresh();

        expect($product->image_url)->toBe($url);
    });

    it('updates value correctly', function (): void {
        $product = createTestProduct(['image_url' => 'https://old.com/old.jpg']);
        $product->image_url = 'https://new.com/new.jpg';
        $product->save();

        $freshProduct = Product::find($product->getKey());

        expect($freshProduct->image_url)->toBe('https://new.com/new.jpg');
    });

    it('can be set to null after having a value', function (): void {
        $product = createTestProduct(['image_url' => 'https://example.com/image.jpg']);
        $product->image_url = null;
        $product->save();

        $freshProduct = Product::find($product->getKey());

        expect($freshProduct->image_url)->toBeNull();
    });
});

describe('Image Cast with InteractsWithDataTables', function (): void {
    it('returns image_url as avatar url', function (): void {
        $url = 'https://example.com/avatar.jpg';
        $product = createTestProduct(['image_url' => $url]);

        expect($product->getAvatarUrl())->toBe($url);
    });

    it('returns null for avatar when image_url is null', function (): void {
        $product = createTestProduct(['image_url' => null]);

        expect($product->getAvatarUrl())->toBeNull();
    });
});
