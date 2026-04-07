<?php

use TeamNiftyGmbH\DataTable\Exports\Concerns\ExportsData;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('ExportsData trait', function (): void {
    it('generates headings from simple columns', function (): void {
        $exporter = new class(['title', 'content'])
        {
            use ExportsData;

            public function __construct(array $columns)
            {
                $this->exportColumns = $columns;
            }
        };

        expect($exporter->headings())->toBe([__('Title'), __('Content')]);
    });

    it('generates headings for dot-notation columns', function (): void {
        $exporter = new class(['user.name'])
        {
            use ExportsData;

            public function __construct(array $columns)
            {
                $this->exportColumns = $columns;
            }
        };

        expect($exporter->headings()[0])->toContain(__('User'))->toContain(' -> ')->toContain(__('Name'));
    });

    it('maps row data for simple columns', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Test']);

        $exporter = new class(['title'])
        {
            use ExportsData;

            public function __construct(array $columns)
            {
                $this->exportColumns = $columns;
            }
        };

        expect($exporter->mapRow($post))->toBe(['title' => 'Test']);
    });

    it('maps row data for relation columns', function (): void {
        $post = createTestPost(['user_id' => $this->user->getKey()]);
        $post->load('user');

        $exporter = new class(['user.name'])
        {
            use ExportsData;

            public function __construct(array $columns)
            {
                $this->exportColumns = $columns;
            }
        };

        expect($exporter->mapRow($post)['user.name'])->toBe($this->user->name);
    });
});
