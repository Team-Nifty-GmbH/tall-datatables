<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use TeamNiftyGmbH\DataTable\Helpers\AggregatableRelationColumn;
use TeamNiftyGmbH\DataTable\Helpers\Icon;
use TeamNiftyGmbH\DataTable\Helpers\SessionFilter;
use Tests\TestCase;

class HelpersTest extends TestCase
{
    #[Test]
    public function it_can_create_aggregatable_relation_column(): void
    {
        // Test with string relation
        $column1 = AggregatableRelationColumn::make('orders', 'amount', 'sum');
        $this->assertEquals('orders', $column1->relation);
        $this->assertEquals('amount', $column1->column);
        $this->assertEquals('sum', $column1->function);
        $this->assertEquals('orders_sum_amount', $column1->alias);

        // Test with array relation
        $column2 = AggregatableRelationColumn::make(['orders' => 'customer.orders'], 'amount', 'avg', 'custom_alias');
        $this->assertIsArray($column2->relation);
        $this->assertEquals('amount', $column2->column);
        $this->assertEquals('avg', $column2->function);
        $this->assertEquals('custom_alias', $column2->alias);
    }

    #[Test]
    public function it_can_create_icon(): void
    {
        $icon = Icon::make('user', 'solid');

        // Test string conversion
        $html = (string) $icon;
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('<svg', $html);

        // Test HTML output
        $html = $icon->toHtml();
        $this->assertNotEmpty($html);

        // Test URL generation
        $url = $icon->getUrl();
        $this->assertNotEmpty($url);
        $this->assertStringContainsString('user', $url);
    }

    #[Test]
    public function it_can_create_session_filter(): void
    {
        // Create a simple session filter
        $filter = SessionFilter::make(
            'test-key',
            function ($query, $datatable) {
                return $query->where('name', 'Test');
            },
            'Test Filter'
        );

        // Check the properties
        $this->assertEquals('test-key', $filter->dataTableCacheKey);
        $this->assertEquals('Test Filter', $filter->name);
        $this->assertFalse($filter->loaded);

        // Check serialization works
        $serialized = serialize($filter);
        $unserialized = unserialize($serialized);

        $this->assertEquals($filter->name, $unserialized->name);
        $this->assertEquals($filter->dataTableCacheKey, $unserialized->dataTableCacheKey);
    }
}
