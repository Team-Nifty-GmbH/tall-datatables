<?php

use Spatie\ModelInfo\Attributes\Attribute as BaseAttribute;
use TeamNiftyGmbH\DataTable\Casts\BcFloat;
use TeamNiftyGmbH\DataTable\Casts\Links\Image;
use TeamNiftyGmbH\DataTable\Casts\Links\Link;
use TeamNiftyGmbH\DataTable\Casts\Money;
use TeamNiftyGmbH\DataTable\Casts\Percentage;
use TeamNiftyGmbH\DataTable\ModelInfo\Attribute;
use Tests\Fixtures\Models\Post;
use Tests\Fixtures\Models\Product;

describe('Attribute::fromBase', function (): void {
    it('creates Attribute from BaseAttribute', function (): void {
        $base = new BaseAttribute(
            name: 'test_field',
            phpType: 'string',
            type: 'varchar',
            increments: false,
            nullable: true,
            default: null,
            primary: false,
            unique: false,
            fillable: true,
            appended: false,
            cast: null,
            virtual: false,
            hidden: false,
        );

        $attribute = Attribute::fromBase($base);

        expect($attribute)
            ->toBeInstanceOf(Attribute::class)
            ->and($attribute->name)->toBe('test_field')
            ->and($attribute->phpType)->toBe('string')
            ->and($attribute->type)->toBe('varchar')
            ->and($attribute->increments)->toBeFalse()
            ->and($attribute->nullable)->toBeTrue()
            ->and($attribute->default)->toBeNull()
            ->and($attribute->primary)->toBeFalse()
            ->and($attribute->unique)->toBeFalse()
            ->and($attribute->fillable)->toBeTrue()
            ->and($attribute->appended)->toBeFalse()
            ->and($attribute->cast)->toBeNull()
            ->and($attribute->virtual)->toBeFalse()
            ->and($attribute->hidden)->toBeFalse();
    });

    it('preserves all properties from base', function (): void {
        $base = new BaseAttribute(
            name: 'id',
            phpType: 'int',
            type: 'integer',
            increments: true,
            nullable: false,
            default: null,
            primary: true,
            unique: true,
            fillable: false,
            appended: false,
            cast: 'integer',
            virtual: false,
            hidden: false,
        );

        $attribute = Attribute::fromBase($base);

        expect($attribute->increments)->toBeTrue()
            ->and($attribute->primary)->toBeTrue()
            ->and($attribute->unique)->toBeTrue()
            ->and($attribute->fillable)->toBeFalse()
            ->and($attribute->cast)->toBe('integer');
    });
});

describe('Attribute::getFormatterType', function (): void {
    it('returns frontend formatter for HasFrontendFormatter cast', function (): void {
        $base = new BaseAttribute(
            name: 'price',
            phpType: 'float',
            type: 'decimal',
            increments: false,
            nullable: false,
            default: null,
            primary: false,
            unique: false,
            fillable: true,
            appended: false,
            cast: Money::class,
            virtual: false,
            hidden: false,
        );

        $attribute = Attribute::fromBase($base);
        $formatter = $attribute->getFormatterType(Product::class);

        expect($formatter)->toBe('money');
    });

    it('returns frontend formatter for BcFloat cast', function (): void {
        $base = new BaseAttribute(
            name: 'quantity',
            phpType: 'float',
            type: 'decimal',
            increments: false,
            nullable: false,
            default: null,
            primary: false,
            unique: false,
            fillable: true,
            appended: false,
            cast: BcFloat::class,
            virtual: false,
            hidden: false,
        );

        $attribute = Attribute::fromBase($base);
        $formatter = $attribute->getFormatterType(Product::class);

        expect($formatter)->toBe('float');
    });

    it('returns frontend formatter for Percentage cast', function (): void {
        $base = new BaseAttribute(
            name: 'discount',
            phpType: 'float',
            type: 'decimal',
            increments: false,
            nullable: false,
            default: null,
            primary: false,
            unique: false,
            fillable: true,
            appended: false,
            cast: Percentage::class,
            virtual: false,
            hidden: false,
        );

        $attribute = Attribute::fromBase($base);
        $formatter = $attribute->getFormatterType(Product::class);

        expect($formatter)->toBe('percentage');
    });

    it('returns frontend formatter for Image cast', function (): void {
        $base = new BaseAttribute(
            name: 'image_url',
            phpType: 'string',
            type: 'varchar',
            increments: false,
            nullable: true,
            default: null,
            primary: false,
            unique: false,
            fillable: true,
            appended: false,
            cast: Image::class,
            virtual: false,
            hidden: false,
        );

        $attribute = Attribute::fromBase($base);
        $formatter = $attribute->getFormatterType(Product::class);

        expect($formatter)->toBe('image');
    });

    it('returns frontend formatter for Link cast', function (): void {
        $base = new BaseAttribute(
            name: 'website',
            phpType: 'string',
            type: 'varchar',
            increments: false,
            nullable: true,
            default: null,
            primary: false,
            unique: false,
            fillable: true,
            appended: false,
            cast: Link::class,
            virtual: false,
            hidden: false,
        );

        $attribute = Attribute::fromBase($base);
        $formatter = $attribute->getFormatterType(Product::class);

        expect($formatter)->toBe('link');
    });

    it('returns lowercased class basename for non-formatter casts', function (): void {
        $base = new BaseAttribute(
            name: 'is_active',
            phpType: 'bool',
            type: 'tinyint',
            increments: false,
            nullable: false,
            default: null,
            primary: false,
            unique: false,
            fillable: true,
            appended: false,
            cast: 'boolean',
            virtual: false,
            hidden: false,
        );

        $attribute = Attribute::fromBase($base);
        $formatter = $attribute->getFormatterType(Product::class);

        expect($formatter)->toBe('boolean');
    });

    it('returns lowercased phpType when cast is null', function (): void {
        $base = new BaseAttribute(
            name: 'description',
            phpType: 'string',
            type: 'text',
            increments: false,
            nullable: true,
            default: null,
            primary: false,
            unique: false,
            fillable: true,
            appended: false,
            cast: null,
            virtual: false,
            hidden: false,
        );

        $attribute = Attribute::fromBase($base);
        $formatter = $attribute->getFormatterType(Product::class);

        expect($formatter)->toBe('string');
    });

    it('resolves accessor cast via model getCasts when cast is accessor', function (): void {
        $base = new BaseAttribute(
            name: 'price',
            phpType: 'float',
            type: 'decimal',
            increments: false,
            nullable: false,
            default: null,
            primary: false,
            unique: false,
            fillable: true,
            appended: false,
            cast: 'accessor',
            virtual: false,
            hidden: false,
        );

        $attribute = Attribute::fromBase($base);
        // Post model has price => BcFloat::class in casts
        $formatter = $attribute->getFormatterType(Post::class);

        expect($formatter)->toBe('float');
    });

    it('resolves attribute cast via model getCasts when cast is attribute', function (): void {
        $base = new BaseAttribute(
            name: 'price',
            phpType: 'float',
            type: 'decimal',
            increments: false,
            nullable: false,
            default: null,
            primary: false,
            unique: false,
            fillable: true,
            appended: false,
            cast: 'attribute',
            virtual: false,
            hidden: false,
        );

        $attribute = Attribute::fromBase($base);
        $formatter = $attribute->getFormatterType(Post::class);

        expect($formatter)->toBe('float');
    });

    it('falls back to phpType class when cast is accessor and not in model casts', function (): void {
        $base = new BaseAttribute(
            name: 'unknown_field',
            phpType: Money::class,
            type: 'decimal',
            increments: false,
            nullable: false,
            default: null,
            primary: false,
            unique: false,
            fillable: true,
            appended: false,
            cast: 'accessor',
            virtual: false,
            hidden: false,
        );

        $attribute = Attribute::fromBase($base);
        $formatter = $attribute->getFormatterType(Post::class);

        expect($formatter)->toBe('money');
    });

    it('accepts model instance instead of string', function (): void {
        $base = new BaseAttribute(
            name: 'price',
            phpType: 'float',
            type: 'decimal',
            increments: false,
            nullable: false,
            default: null,
            primary: false,
            unique: false,
            fillable: true,
            appended: false,
            cast: Money::class,
            virtual: false,
            hidden: false,
        );

        $attribute = Attribute::fromBase($base);
        $formatter = $attribute->getFormatterType(new Product());

        expect($formatter)->toBe('money');
    });

    it('returns lowercased basename for non-class cast strings', function (): void {
        $base = new BaseAttribute(
            name: 'data',
            phpType: 'array',
            type: 'json',
            increments: false,
            nullable: true,
            default: null,
            primary: false,
            unique: false,
            fillable: true,
            appended: false,
            cast: 'array',
            virtual: false,
            hidden: false,
        );

        $attribute = Attribute::fromBase($base);
        $formatter = $attribute->getFormatterType(Product::class);

        expect($formatter)->toBe('array');
    });
});
