<?php

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use TeamNiftyGmbH\DataTable\Casts\BcFloat;
use TeamNiftyGmbH\DataTable\Casts\Links\Image;
use TeamNiftyGmbH\DataTable\Casts\Links\Link;
use TeamNiftyGmbH\DataTable\Casts\Money;
use TeamNiftyGmbH\DataTable\Casts\Percentage;
use Tests\TestCase;

class CastsTest extends TestCase
{
    #[Test]
    public function bcfloat_cast_returns_correct_formatter(): void
    {
        $formatter = BcFloat::getFrontendFormatter();
        $this->assertEquals('float', $formatter);

        // Test the cast functionality with integer value
        $bcFloat = new BcFloat();
        $model = new class() extends Model
        {
            public function get_attribute_value($key)
            {
                return 123;
            }
        };

        // Integer should be formatted with 2 decimal places
        $result = $bcFloat->get($model, 'number', 123, []);
        $this->assertEquals(123.00, $result);

        // Floating point values should be preserved
        $model = new class() extends Model
        {
            public function getAttributeValue($key)
            {
                return 123.45;
            }
        };

        $result = $bcFloat->get($model, 'number', 123.45, []);
        $this->assertEquals(123.45, $result);
    }

    #[Test]
    public function image_cast_returns_correct_formatter(): void
    {
        $formatter = Image::getFrontendFormatter();
        $this->assertEquals('image', $formatter);

        // Test the cast functionality
        $image = new Image();
        $model = new class() extends Model
        {
            public function get_attribute_value($key)
            {
                return 'https://example.com/image.jpg';
            }
        };

        $result = $image->get($model, 'avatar', 'https://example.com/image.jpg', []);
        $this->assertEquals('https://example.com/image.jpg', $result);
    }

    #[Test]
    public function link_cast_returns_correct_formatter(): void
    {
        $formatter = Link::getFrontendFormatter();
        $this->assertEquals('link', $formatter);

        // Test the cast functionality
        $link = new Link();
        $model = new class() extends Model
        {
            public function get_attribute_value($key)
            {
                return 'https://example.com';
            }
        };

        $result = $link->get($model, 'website', 'https://example.com', []);
        $this->assertEquals('https://example.com', $result);
    }

    #[Test]
    public function money_cast_returns_correct_formatter(): void
    {
        $formatter = Money::getFrontendFormatter();
        $this->assertEquals('money', $formatter);

        // Test the cast functionality
        $money = new Money();
        $model = new class() extends Model
        {
            public function get_attribute_value($key)
            {
                return 123.45;
            }
        };

        $result = $money->get($model, 'amount', 123.45, []);
        $this->assertEquals(123.45, $result);
    }

    #[Test]
    public function percentage_cast_returns_correct_formatter(): void
    {
        $formatter = Percentage::getFrontendFormatter();
        $this->assertEquals('percentage', $formatter);

        // Test the cast functionality
        $percentage = new Percentage();
        $model = new class() extends Model
        {
            public function get_attribute_value($key)
            {
                return 0.75;
            }
        };

        $result = $percentage->get($model, 'ratio', 0.75, []);
        $this->assertEquals(0.75, $result);
    }
}
