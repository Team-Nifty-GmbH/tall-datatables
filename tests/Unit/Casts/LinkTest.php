<?php

use TeamNiftyGmbH\DataTable\Casts\Links\Link;
use TeamNiftyGmbH\DataTable\Contracts\HasFrontendFormatter;
use Tests\Fixtures\Models\Product;

describe('Link Cast', function (): void {
    it('implements HasFrontendFormatter interface', function (): void {
        expect(Link::class)->toImplement(HasFrontendFormatter::class);
    });

    it('returns link formatter name', function (): void {
        expect(Link::getFrontendFormatter())->toBe('link');
    });

    it('returns url value as-is', function (): void {
        $product = createTestProduct(['website' => 'https://example.com']);

        expect($product->website)->toBe('https://example.com');
    });

    it('handles null values', function (): void {
        $product = createTestProduct(['website' => null]);

        expect($product->website)->toBeNull();
    });

    it('handles empty string', function (): void {
        $product = createTestProduct(['website' => '']);

        expect($product->website)->toBe('');
    });

    it('handles http urls', function (): void {
        $product = createTestProduct(['website' => 'http://example.com']);

        expect($product->website)->toBe('http://example.com');
    });

    it('handles urls with paths', function (): void {
        $url = 'https://example.com/path/to/page';
        $product = createTestProduct(['website' => $url]);

        expect($product->website)->toBe($url);
    });

    it('handles urls with query parameters', function (): void {
        $url = 'https://example.com?param=value&other=123';
        $product = createTestProduct(['website' => $url]);

        expect($product->website)->toBe($url);
    });

    it('handles urls with fragments', function (): void {
        $url = 'https://example.com/page#section';
        $product = createTestProduct(['website' => $url]);

        expect($product->website)->toBe($url);
    });

    it('handles complex urls', function (): void {
        $url = 'https://user:pass@example.com:8080/path?query=value#fragment';
        $product = createTestProduct(['website' => $url]);

        expect($product->website)->toBe($url);
    });
});

describe('Link Cast Persistence', function (): void {
    it('persists value to database', function (): void {
        $product = createTestProduct(['website' => 'https://example.com']);
        $product->save();
        $product->refresh();

        expect($product->website)->toBe('https://example.com');
    });

    it('updates value correctly', function (): void {
        $product = createTestProduct(['website' => 'https://old.com']);
        $product->website = 'https://new.com';
        $product->save();

        $freshProduct = Product::find($product->getKey());

        expect($freshProduct->website)->toBe('https://new.com');
    });

    it('can be set to null after having a value', function (): void {
        $product = createTestProduct(['website' => 'https://example.com']);
        $product->website = null;
        $product->save();

        $freshProduct = Product::find($product->getKey());

        expect($freshProduct->website)->toBeNull();
    });
});

describe('Link Cast Query Operations', function (): void {
    it('works with like queries', function (): void {
        createTestProduct(['website' => 'https://google.com', 'name' => 'Google']);
        createTestProduct(['website' => 'https://github.com', 'name' => 'GitHub']);
        createTestProduct(['website' => 'https://gitlab.com', 'name' => 'GitLab']);

        $gitSites = Product::where('website', 'like', '%git%')->count();

        expect($gitSites)->toBe(2);
    });

    it('works with null checks', function (): void {
        createTestProduct(['website' => 'https://example.com', 'name' => 'With']);
        createTestProduct(['website' => null, 'name' => 'Without']);

        $withWebsite = Product::whereNotNull('website')->count();
        $withoutWebsite = Product::whereNull('website')->count();

        expect($withWebsite)->toBe(1);
        expect($withoutWebsite)->toBe(1);
    });
});

describe('Link Cast Direct Methods', function (): void {
    it('set method passes value through unchanged', function (): void {
        $cast = new Link();
        $model = new Product();

        expect($cast->set($model, 'website', 'https://example.com', []))
            ->toBe('https://example.com');
        expect($cast->set($model, 'website', null, []))->toBeNull();
        expect($cast->set($model, 'website', '', []))->toBe('');
    });

    it('getFrontendFormatter returns link', function (): void {
        expect(Link::getFrontendFormatter())->toBe('link');
        expect(Link::getFrontendFormatter('extra'))->toBe('link');
    });

    it('handles special characters in urls via get', function (): void {
        $url = 'https://example.com/path?q=hello&lang=de';
        $product = createTestProduct(['website' => $url]);

        expect($product->website)->toBe($url);
    });

    it('delegates to attribute mutator when model has one', function (): void {
        $cast = new Link();

        $model = new class() extends Illuminate\Database\Eloquent\Model
        {
            protected $guarded = ['id'];

            protected $table = 'products';

            protected function casts(): array
            {
                return ['website' => Link::class];
            }

            public function website(): Illuminate\Database\Eloquent\Casts\Attribute
            {
                return Illuminate\Database\Eloquent\Casts\Attribute::make(
                    get: fn ($value) => 'mutated:' . $value,
                );
            }
        };

        $model->setRawAttributes(['website' => 'https://example.com']);

        $result = $cast->get($model, 'website', 'https://example.com', ['website' => 'https://example.com']);

        expect($result)->toBe('mutated:https://example.com');
    });
});
