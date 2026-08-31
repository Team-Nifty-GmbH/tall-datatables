<?php

use Illuminate\Database\Eloquent\Model;
use TeamNiftyGmbH\DataTable\Exports\Concerns\ExportsData;
use TeamNiftyGmbH\DataTable\Formatters\ArrayFormatter;
use TeamNiftyGmbH\DataTable\Formatters\BadgeFormatter;
use TeamNiftyGmbH\DataTable\Formatters\BooleanFormatter;
use TeamNiftyGmbH\DataTable\Formatters\Contracts\Formatter;
use TeamNiftyGmbH\DataTable\Formatters\EnumFormatter;
use TeamNiftyGmbH\DataTable\Formatters\LinkFormatter;
use TeamNiftyGmbH\DataTable\Formatters\PercentageFormatter;
use TeamNiftyGmbH\DataTable\Formatters\StringFormatter;

class StubFormatter implements Formatter
{
    public function format(mixed $value, array $context = []): string
    {
        return '<span class="badge">' . e($value) . '</span>';
    }
}

class ExportsDataTestClass
{
    use ExportsData;

    public function __construct(array $columns, array $formatters = [])
    {
        $this->exportColumns = $columns;
        $this->exportFormatters = $formatters;
    }

    public function testMapRow($row): array
    {
        return $this->mapRow($row);
    }
}

describe('ExportsData formatted export', function (): void {
    test('mapRow returns raw values when no formatters set', function (): void {
        $exporter = new ExportsDataTestClass(['title', 'status']);

        $row = new class() extends Model
        {
            protected $guarded = [];

            public function __construct()
            {
                parent::__construct(['title' => 'Test', 'status' => 'active']);
            }
        };

        $result = $exporter->testMapRow($row);

        expect($result)->toBe(['title' => 'Test', 'status' => 'active']);
    });

    test('mapRow applies formatters and strips HTML when formatters set', function (): void {
        $exporter = new ExportsDataTestClass(
            ['title', 'status'],
            ['status' => new StubFormatter()]
        );

        $row = new class() extends Model
        {
            protected $guarded = [];

            public function __construct()
            {
                parent::__construct(['title' => 'Test', 'status' => 'active']);
            }
        };

        $result = $exporter->testMapRow($row);

        expect($result['status'])->toBe('active');
        expect($result['title'])->toBe('Test');
    });

    test('mapRow converts boolean SVG to Yes/No text', function (): void {
        $exporter = new ExportsDataTestClass(
            ['is_active'],
            ['is_active' => new BooleanFormatter()]
        );

        $trueRow = new class() extends Model
        {
            protected $guarded = [];

            public function __construct()
            {
                parent::__construct(['is_active' => true]);
            }
        };

        $falseRow = new class() extends Model
        {
            protected $guarded = [];

            public function __construct()
            {
                parent::__construct(['is_active' => false]);
            }
        };

        expect($exporter->testMapRow($trueRow)['is_active'])->toBe(__('Yes'));
        expect($exporter->testMapRow($falseRow)['is_active'])->toBe(__('No'));
    });

    test('mapRow handles null values with formatters', function (): void {
        $exporter = new ExportsDataTestClass(
            ['status'],
            ['status' => new StubFormatter()]
        );

        $row = new class() extends Model
        {
            protected $guarded = [];

            public function __construct()
            {
                parent::__construct(['status' => null]);
            }
        };

        expect($exporter->testMapRow($row)['status'])->toBeNull();
    });
    test('mapRow formats every element of an array value on its own', function (): void {
        $exporter = new ExportsDataTestClass(
            ['rates'],
            ['rates' => new PercentageFormatter()]
        );

        $row = new class() extends Model
        {
            protected $guarded = [];

            public function __construct()
            {
                parent::__construct(['rates' => [0.1, 0.255]]);
            }
        };

        expect($exporter->testMapRow($row)['rates'])->toBe('10 %; 25,50 %');
    });

    test('mapRow formats an array value for every scalar formatter', function (Formatter $formatter, string $expected): void {
        $exporter = new ExportsDataTestClass(['values'], ['values' => $formatter]);

        $row = new class() extends Model
        {
            protected $guarded = [];

            public function __construct()
            {
                parent::__construct(['values' => ['a', 'b']]);
            }
        };

        expect($exporter->testMapRow($row)['values'])->toBe($expected);
    })->with([
        'string' => [new StringFormatter(), 'a; b'],
        'badge' => [new BadgeFormatter(), 'a; b'],
        'enum' => [new EnumFormatter(), 'A; B'],
        'link' => [new LinkFormatter(), 'a; b'],
    ]);

    test('mapRow lets an array formatter render the whole array', function (): void {
        $exporter = new ExportsDataTestClass(
            ['tags'],
            ['tags' => new ArrayFormatter()]
        );

        $row = new class() extends Model
        {
            protected $guarded = [];

            public function __construct()
            {
                parent::__construct(['tags' => ['first', 'second']]);
            }
        };

        expect($exporter->testMapRow($row)['tags'])->toBe('first second');
    });

    test('mapRow joins an array value when no formatter is set', function (): void {
        $exporter = new ExportsDataTestClass(['tags']);

        $row = new class() extends Model
        {
            protected $guarded = [];

            public function __construct()
            {
                parent::__construct(['tags' => ['first', 'second']]);
            }
        };

        expect($exporter->testMapRow($row)['tags'])->toBe('first; second');
    });
});
