<?php

use TeamNiftyGmbH\DataTable\Filters\FilterParser;

describe('FilterParser::parse', function (): void {
    it('returns null for empty input', function (): void {
        $parser = new FilterParser();

        expect($parser->parse('', 'title'))->toBeNull();
    });

    it('returns null for whitespace-only input', function (): void {
        $parser = new FilterParser();

        expect($parser->parse('   ', 'title'))->toBeNull();
    });

    it('parses plain text to like filter with wildcards', function (): void {
        $parser = new FilterParser();
        $result = $parser->parse('hello', 'title');

        expect($result)->toBe([
            'column' => 'title',
            'operator' => 'like',
            'value' => '%hello%',
        ]);
    });

    it('parses =value to exact match filter', function (): void {
        $parser = new FilterParser();
        $result = $parser->parse('=active', 'status');

        expect($result)->toBe([
            'column' => 'status',
            'operator' => '=',
            'value' => 'active',
        ]);
    });

    it('parses =numeric value as number', function (): void {
        $parser = new FilterParser();
        $result = $parser->parse('=100', 'price');

        expect($result['operator'])->toBe('=')
            ->and($result['value'])->toBe(100);
    });

    it('parses !=value to not equal filter', function (): void {
        $parser = new FilterParser();
        $result = $parser->parse('!=inactive', 'status');

        expect($result)->toBe([
            'column' => 'status',
            'operator' => '!=',
            'value' => 'inactive',
        ]);
    });

    it('parses >value to greater than filter', function (): void {
        $parser = new FilterParser();
        $result = $parser->parse('>100', 'price');

        expect($result)->toBe([
            'column' => 'price',
            'operator' => '>',
            'value' => 100,
        ]);
    });

    it('parses <value to less than filter', function (): void {
        $parser = new FilterParser();
        $result = $parser->parse('<200', 'price');

        expect($result)->toBe([
            'column' => 'price',
            'operator' => '<',
            'value' => 200,
        ]);
    });

    it('parses >=value to greater than or equal filter', function (): void {
        $parser = new FilterParser();
        $result = $parser->parse('>=50', 'price');

        expect($result)->toBe([
            'column' => 'price',
            'operator' => '>=',
            'value' => 50,
        ]);
    });

    it('parses <=value to less than or equal filter', function (): void {
        $parser = new FilterParser();
        $result = $parser->parse('<=300', 'price');

        expect($result)->toBe([
            'column' => 'price',
            'operator' => '<=',
            'value' => 300,
        ]);
    });

    it('parses value1..value2 to between filter', function (): void {
        $parser = new FilterParser();
        $result = $parser->parse('100..200', 'price');

        expect($result)->toBe([
            'column' => 'price',
            'operator' => 'between',
            'value' => [100, 200],
        ]);
    });

    it('parses NULL to is null filter', function (): void {
        $parser = new FilterParser();
        $result = $parser->parse('NULL', 'deleted_at');

        expect($result)->toBe([
            'column' => 'deleted_at',
            'operator' => 'is null',
            'value' => null,
        ]);
    });

    it('parses !NULL to is not null filter', function (): void {
        $parser = new FilterParser();
        $result = $parser->parse('!NULL', 'deleted_at');

        expect($result)->toBe([
            'column' => 'deleted_at',
            'operator' => 'is not null',
            'value' => null,
        ]);
    });

    it('trims whitespace from input', function (): void {
        $parser = new FilterParser();
        $result = $parser->parse('  hello  ', 'title');

        expect($result)->not->toBeNull()
            ->and($result['value'])->toBe('%hello%');
    });

    it('parses float range values', function (): void {
        $parser = new FilterParser();
        $result = $parser->parse('10.5..99.9', 'price');

        expect($result['operator'])->toBe('between')
            ->and($result['value'][0])->toBe(10.5)
            ->and($result['value'][1])->toBe(99.9);
    });

    it('preserves string values in range', function (): void {
        $parser = new FilterParser();
        $result = $parser->parse('2024-01-01..2024-12-31', 'created_at');

        expect($result['operator'])->toBe('between')
            ->and($result['value'][0])->toBe('2024-01-01')
            ->and($result['value'][1])->toBe('2024-12-31');
    });

    it('uses the correct column name in the result', function (): void {
        $parser = new FilterParser();
        $result = $parser->parse('test', 'my_custom_column');

        expect($result['column'])->toBe('my_custom_column');
    });

    test('escapes SQL wildcard percent in like filter', function (): void {
        $parser = new FilterParser();
        $result = $parser->parse('100%', 'title');

        expect($result['value'])->toBe('%100\%%');
    });

    test('escapes SQL wildcard underscore in like filter', function (): void {
        $parser = new FilterParser();
        $result = $parser->parse('test_value', 'title');

        expect($result['value'])->toBe('%test\_value%');
    });
});
