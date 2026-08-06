<?php

use TeamNiftyGmbH\DataTable\Exports\Concerns\ExportsData;
use TeamNiftyGmbH\DataTable\Formatters\ArrayFormatter;
use TeamNiftyGmbH\DataTable\Formatters\StringFormatter;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);

    $this->exporter = fn (array $columns, array $formatters) => new class($columns, $formatters)
    {
        use ExportsData;

        public function __construct(array $columns, array $formatters)
        {
            $this->exportColumns = $columns;
            $this->exportFormatters = $formatters;
        }
    };
});

it('writes ampersands and quotes as themselves, not as html entities', function (): void {
    $post = createTestPost([
        'user_id' => $this->user->getKey(),
        'title' => 'Ben & Jerry\'s "Special"',
    ]);

    $exporter = ($this->exporter)(['title'], ['title' => new StringFormatter()]);

    expect($exporter->mapRow($post)['title'])->toBe('Ben & Jerry\'s "Special"');
});

it('writes plain characters for values behind an array formatter', function (): void {
    $post = createTestPost([
        'user_id' => $this->user->getKey(),
        'title' => 'Ben & Jerry\'s',
    ]);

    $exporter = ($this->exporter)(
        ['title'],
        ['title' => new ArrayFormatter(new StringFormatter())]
    );

    expect($exporter->mapRow($post)['title'])->toBe('Ben & Jerry\'s');
});

it('still strips html tags', function (): void {
    $post = createTestPost([
        'user_id' => $this->user->getKey(),
        'title' => '<b>bold</b> & plain',
    ]);

    $exporter = ($this->exporter)(['title'], ['title' => new StringFormatter()]);

    expect($exporter->mapRow($post)['title'])->toBe('bold & plain');
});
