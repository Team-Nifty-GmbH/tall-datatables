<?php

use TeamNiftyGmbH\DataTable\Helpers\AggregatableRelationColumn;

describe('AggregatableRelationColumn Constructor', function (): void {
    it('sets relation from string', function (): void {
        $col = new AggregatableRelationColumn('posts', 'id');

        expect($col->relation)->toBe('posts');
    });

    it('sets column property', function (): void {
        $col = new AggregatableRelationColumn('posts', 'total');

        expect($col->column)->toBe('total');
    });

    it('defaults function to sum', function (): void {
        $col = new AggregatableRelationColumn('posts', 'id');

        expect($col->function)->toBe('sum');
    });

    it('auto-generates alias from relation, function, and column', function (): void {
        $col = new AggregatableRelationColumn('orders', 'total', 'sum');

        expect($col->alias)->toBe('orders_sum_total');
    });

    it('converts camelCase relation to snake_case in alias', function (): void {
        $col = new AggregatableRelationColumn('orderItems', 'price', 'avg');

        expect($col->alias)->toBe('order_items_avg_price');
    });

    it('uses custom alias when provided', function (): void {
        $col = new AggregatableRelationColumn('posts', 'id', 'count', 'total_posts');

        expect($col->alias)->toBe('total_posts');
    });
});

describe('AggregatableRelationColumn with aggregate functions', function (): void {
    it('accepts count function', function (): void {
        $col = new AggregatableRelationColumn('comments', 'id', 'count');

        expect($col->function)->toBe('count')
            ->and($col->alias)->toBe('comments_count_id');
    });

    it('accepts avg function', function (): void {
        $col = new AggregatableRelationColumn('ratings', 'score', 'avg');

        expect($col->function)->toBe('avg')
            ->and($col->alias)->toBe('ratings_avg_score');
    });

    it('accepts min function', function (): void {
        $col = new AggregatableRelationColumn('prices', 'amount', 'min');

        expect($col->function)->toBe('min')
            ->and($col->alias)->toBe('prices_min_amount');
    });

    it('accepts max function', function (): void {
        $col = new AggregatableRelationColumn('prices', 'amount', 'max');

        expect($col->function)->toBe('max')
            ->and($col->alias)->toBe('prices_max_amount');
    });
});

describe('AggregatableRelationColumn make factory', function (): void {
    it('creates an instance', function (): void {
        $col = AggregatableRelationColumn::make('items', 'price');

        expect($col)->toBeInstanceOf(AggregatableRelationColumn::class);
    });

    it('passes all parameters correctly', function (): void {
        $col = AggregatableRelationColumn::make(
            relation: 'orders',
            column: 'amount',
            function: 'avg',
            alias: 'order_average'
        );

        expect($col->relation)->toBe('orders')
            ->and($col->column)->toBe('amount')
            ->and($col->function)->toBe('avg')
            ->and($col->alias)->toBe('order_average');
    });

    it('auto-generates alias when not provided', function (): void {
        $col = AggregatableRelationColumn::make('products', 'quantity', 'sum');

        expect($col->alias)->toBe('products_sum_quantity');
    });
});

describe('AggregatableRelationColumn with array relation', function (): void {
    it('accepts array relation with closure constraint', function (): void {
        $col = new AggregatableRelationColumn(
            ['comments' => fn ($q) => $q->where('approved', true)],
            'id',
            'count'
        );

        expect($col->relation)->toBeArray();
    });

    it('generates alias from array key', function (): void {
        $col = new AggregatableRelationColumn(
            ['approvedComments' => fn ($q) => $q->where('approved', true)],
            'id',
            'count'
        );

        expect($col->alias)->toBe('approved_comments_count_id');
    });

    it('restructures array relation key to include as clause', function (): void {
        $col = new AggregatableRelationColumn(
            ['activeUsers' => fn ($q) => $q->where('active', true)],
            'id',
            'count'
        );

        $key = array_key_first($col->relation);

        expect($key)->toContain(' as ');
    });

    it('preserves closure in restructured array relation', function (): void {
        $closure = fn ($q) => $q->where('visible', true);
        $col = new AggregatableRelationColumn(
            ['visiblePosts' => $closure],
            'likes',
            'sum'
        );

        $values = array_values($col->relation);

        expect($values[0])->toBeInstanceOf(Closure::class);
    });

    it('uses custom alias over generated alias for array relations', function (): void {
        $col = new AggregatableRelationColumn(
            ['comments' => fn ($q) => $q->where('approved', true)],
            'id',
            'count',
            'custom_count'
        );

        expect($col->alias)->toBe('custom_count');
    });
});
