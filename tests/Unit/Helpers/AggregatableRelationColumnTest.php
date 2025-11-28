<?php

use TeamNiftyGmbH\DataTable\Helpers\AggregatableRelationColumn;

describe('AggregatableRelationColumn', function (): void {
    it('can be instantiated with constructor', function (): void {
        $column = new AggregatableRelationColumn('comments', 'id');

        expect($column)->toBeInstanceOf(AggregatableRelationColumn::class);
    });

    it('can be created with make method', function (): void {
        $column = AggregatableRelationColumn::make('comments', 'id');

        expect($column)->toBeInstanceOf(AggregatableRelationColumn::class);
    });

    it('stores relation name', function (): void {
        $column = AggregatableRelationColumn::make('comments', 'id');

        expect($column->relation)->toBe('comments');
    });

    it('stores column name', function (): void {
        $column = AggregatableRelationColumn::make('comments', 'likes');

        expect($column->column)->toBe('likes');
    });

    it('defaults function to sum', function (): void {
        $column = AggregatableRelationColumn::make('comments', 'id');

        expect($column->function)->toBe('sum');
    });

    it('can specify count function', function (): void {
        $column = AggregatableRelationColumn::make('comments', 'id', 'count');

        expect($column->function)->toBe('count');
    });

    it('can specify avg function', function (): void {
        $column = AggregatableRelationColumn::make('orders', 'total', 'avg');

        expect($column->function)->toBe('avg');
    });

    it('can specify min function', function (): void {
        $column = AggregatableRelationColumn::make('prices', 'amount', 'min');

        expect($column->function)->toBe('min');
    });

    it('can specify max function', function (): void {
        $column = AggregatableRelationColumn::make('prices', 'amount', 'max');

        expect($column->function)->toBe('max');
    });

    it('auto-generates alias from relation and function', function (): void {
        $column = AggregatableRelationColumn::make('comments', 'id', 'count');

        expect($column->alias)->toBe('comments_count_id');
    });

    it('auto-generates alias with sum function', function (): void {
        $column = AggregatableRelationColumn::make('orders', 'total', 'sum');

        expect($column->alias)->toBe('orders_sum_total');
    });

    it('auto-generates alias with avg function', function (): void {
        $column = AggregatableRelationColumn::make('ratings', 'score', 'avg');

        expect($column->alias)->toBe('ratings_avg_score');
    });

    it('can specify custom alias', function (): void {
        $column = AggregatableRelationColumn::make('comments', 'id', 'count', 'total_comments');

        expect($column->alias)->toBe('total_comments');
    });

    it('converts camelCase relation to snake_case in alias', function (): void {
        $column = AggregatableRelationColumn::make('orderItems', 'quantity', 'sum');

        expect($column->alias)->toBe('order_items_sum_quantity');
    });
});

describe('AggregatableRelationColumn with Array Relations', function (): void {
    it('handles array relation with constraint', function (): void {
        $column = new AggregatableRelationColumn(
            ['comments' => fn ($query) => $query->where('approved', true)],
            'id',
            'count'
        );

        expect($column->relation)->toBeArray();
    });

    it('generates alias from array relation key', function (): void {
        $column = new AggregatableRelationColumn(
            ['comments' => fn ($query) => $query->where('approved', true)],
            'id',
            'count'
        );

        expect($column->alias)->toBe('comments_count_id');
    });

    it('transforms array relation with alias', function (): void {
        $column = new AggregatableRelationColumn(
            ['approvedComments' => fn ($query) => $query->where('approved', true)],
            'id',
            'count'
        );

        // The relation array should be transformed to include the alias
        expect($column->relation)->toBeArray();
        expect(array_key_first($column->relation))->toContain('as');
    });

    it('custom alias overrides auto-generated alias for array relation', function (): void {
        $column = new AggregatableRelationColumn(
            ['comments' => fn ($query) => $query->where('approved', true)],
            'id',
            'count',
            'my_custom_alias'
        );

        expect($column->alias)->toBe('my_custom_alias');
    });
});

describe('AggregatableRelationColumn Make Method Parameters', function (): void {
    it('make method accepts all parameters', function (): void {
        $column = AggregatableRelationColumn::make(
            relation: 'items',
            column: 'price',
            function: 'avg',
            alias: 'average_price'
        );

        expect($column->relation)->toBe('items');
        expect($column->column)->toBe('price');
        expect($column->function)->toBe('avg');
        expect($column->alias)->toBe('average_price');
    });

    it('make method with positional arguments', function (): void {
        $column = AggregatableRelationColumn::make('orders', 'amount', 'sum', 'total_amount');

        expect($column->relation)->toBe('orders');
        expect($column->column)->toBe('amount');
        expect($column->function)->toBe('sum');
        expect($column->alias)->toBe('total_amount');
    });
});

describe('AggregatableRelationColumn Use Cases', function (): void {
    it('can count related records', function (): void {
        $column = AggregatableRelationColumn::make('posts', 'id', 'count');

        expect($column->function)->toBe('count');
        expect($column->alias)->toBe('posts_count_id');
    });

    it('can sum order totals', function (): void {
        $column = AggregatableRelationColumn::make('orders', 'total', 'sum', 'revenue');

        expect($column->function)->toBe('sum');
        expect($column->alias)->toBe('revenue');
    });

    it('can average ratings', function (): void {
        $column = AggregatableRelationColumn::make('reviews', 'rating', 'avg', 'avg_rating');

        expect($column->function)->toBe('avg');
        expect($column->alias)->toBe('avg_rating');
    });

    it('can find minimum price', function (): void {
        $column = AggregatableRelationColumn::make('variants', 'price', 'min', 'lowest_price');

        expect($column->function)->toBe('min');
        expect($column->alias)->toBe('lowest_price');
    });

    it('can find maximum quantity', function (): void {
        $column = AggregatableRelationColumn::make('inventory', 'quantity', 'max', 'max_stock');

        expect($column->function)->toBe('max');
        expect($column->alias)->toBe('max_stock');
    });
});
