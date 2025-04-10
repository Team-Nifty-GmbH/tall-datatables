<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Spatie\ModelInfo\Attributes\Attribute as BaseAttribute;
use TeamNiftyGmbH\DataTable\Casts\Money;
use TeamNiftyGmbH\DataTable\ModelInfo\Attribute;
use Tests\Models\User;
use Tests\TestCase;

class ModelInfoAttributeTest extends TestCase
{
    #[Test]
    public function it_can_create_from_base_attribute(): void
    {
        // Create a base attribute
        $baseAttribute = new BaseAttribute(
            'test_attr',
            'string',
            'varchar',
            false,
            true,
            null,
            false,
            false,
            true,
            false,
            null,
            false,
            false
        );

        // Convert to our custom attribute
        $attribute = Attribute::fromBase($baseAttribute);

        // Check properties were copied correctly
        $this->assertEquals('test_attr', $attribute->name);
        $this->assertEquals('string', $attribute->phpType);
        $this->assertEquals('varchar', $attribute->type);
        $this->assertFalse($attribute->increments);
        $this->assertTrue($attribute->nullable);
        $this->assertFalse($attribute->virtual);
    }

    #[Test]
    public function it_can_get_formatter_type(): void
    {
        // Create an attribute with a cast that implements HasFrontendFormatter
        $baseAttribute = new BaseAttribute(
            'amount',
            'float',
            'decimal',
            false,
            false,
            null,
            false,
            false,
            true,
            false,
            Money::class,
            false,
            false
        );

        $attribute = Attribute::fromBase($baseAttribute);

        // Test getting formatter type
        $formatterType = $attribute->getFormatterType(User::class);

        // Money cast should return 'money' as formatter
        $this->assertEquals('money', $formatterType);

        // Test standard PHP type
        $baseAttribute = new BaseAttribute(
            'is_active',
            'bool',
            'tinyint',
            false,
            false,
            null,
            false,
            false,
            true,
            false,
            'boolean',
            false,
            false
        );

        $attribute = Attribute::fromBase($baseAttribute);
        $formatterType = $attribute->getFormatterType(User::class);

        // Should return the PHP type or cast name
        $this->assertEquals('boolean', $formatterType);
    }
}
