<?php

use TeamNiftyGmbH\DataTable\Helpers\AggregatableRelationColumn;

describe('AggregatableRelationColumn', function (): void {
    it('can create an aggregatable relation column', function (): void {
        $col = new AggregatableRelationColumn(
            relation: 'comments',
            column: 'id',
            function: 'count',
            alias: 'comments_count'
        );

        expect($col)
            ->toBeInstanceOf(AggregatableRelationColumn::class)
            ->and($col->relation)->toBe('comments')
            ->and($col->column)->toBe('id')
            ->and($col->function)->toBe('count')
            ->and($col->alias)->toBe('comments_count');
    });

    it('can create sum aggregate', function (): void {
        $col = new AggregatableRelationColumn(
            relation: 'orderItems',
            column: 'total',
            function: 'sum',
            alias: 'order_items_sum_total'
        );

        expect($col->function)->toBe('sum')
            ->and($col->alias)->toBe('order_items_sum_total');
    });

    it('can create avg aggregate', function (): void {
        $col = new AggregatableRelationColumn(
            relation: 'ratings',
            column: 'score',
            function: 'avg',
            alias: 'ratings_avg_score'
        );

        expect($col->function)->toBe('avg');
    });

    it('can create min aggregate', function (): void {
        $col = new AggregatableRelationColumn(
            relation: 'prices',
            column: 'amount',
            function: 'min',
            alias: 'prices_min_amount'
        );

        expect($col->function)->toBe('min');
    });

    it('can create max aggregate', function (): void {
        $col = new AggregatableRelationColumn(
            relation: 'prices',
            column: 'amount',
            function: 'max',
            alias: 'prices_max_amount'
        );

        expect($col->function)->toBe('max');
    });

    it('handles nested relations', function (): void {
        $col = new AggregatableRelationColumn(
            relation: 'posts.comments',
            column: 'id',
            function: 'count',
            alias: 'posts_comments_count'
        );

        expect($col->relation)->toBe('posts.comments');
    });
});
