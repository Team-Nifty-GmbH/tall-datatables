<?php

use Livewire\Livewire;
use Maatwebsite\Excel\ExcelServiceProvider;
use Tests\Fixtures\Livewire\NonExportablePostDataTable;
use Tests\Fixtures\Livewire\PostDataTable;
use Tests\Fixtures\Livewire\PostWithRelationsDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('SupportsExporting', function (): void {
    describe('isExportable property', function (): void {
        it('defaults to true', function (): void {
            $component = Livewire::test(PostDataTable::class);

            expect($component->get('isExportable'))->toBeTrue();
        });

        it('can be set to false', function (): void {
            $component = Livewire::test(NonExportablePostDataTable::class);

            expect($component->get('isExportable'))->toBeFalse();
        });
    });

    describe('exportColumns property', function (): void {
        it('defaults to an empty array', function (): void {
            $component = Livewire::test(PostDataTable::class);

            expect($component->get('exportColumns'))->toBe([]);
        });
    });

    describe('getExportableColumns', function (): void {
        it('returns an array', function (): void {
            $component = Livewire::test(PostDataTable::class);

            $result = $component->instance()->getExportableColumns();

            expect($result)->toBeArray();
        });

        it('includes all enabled columns', function (): void {
            $component = Livewire::test(PostDataTable::class);

            $result = $component->instance()->getExportableColumns();

            expect($result)->toContain('title')
                ->toContain('content')
                ->toContain('price')
                ->toContain('is_published')
                ->toContain('created_at');
        });

        it('merges availableCols with enabledCols', function (): void {
            $component = Livewire::test(PostDataTable::class);

            $result = $component->instance()->getExportableColumns();

            // availableCols is ['*'], enabledCols has specific cols
            // merged result should contain all unique values from both
            expect($result)->toContain('*')
                ->toContain('title');
        });

        it('returns unique values only', function (): void {
            $component = Livewire::test(PostDataTable::class);

            $result = $component->instance()->getExportableColumns();

            expect(count($result))->toBe(count(array_unique($result)));
        });

        it('includes relation columns when available', function (): void {
            $component = Livewire::test(PostWithRelationsDataTable::class);

            $result = $component->instance()->getExportableColumns();

            expect($result)->toContain('user.name')
                ->toContain('user.email');
        });

        it('can be called as a renderless Livewire action', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('getExportableColumns');

            expect($component->instance())->toBeInstanceOf(PostDataTable::class);
        });
    });

    describe('export', function (): void {
        beforeEach(function (): void {
            // Register the Excel service provider for export tests
            app()->register(ExcelServiceProvider::class);
        });

        it('returns a download response', function (): void {
            createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Export Test']);

            $component = Livewire::test(PostDataTable::class)
                ->call('loadData');

            $response = $component->instance()->export(['title', 'content']);

            expect($response)->toBeInstanceOf(Symfony\Component\HttpFoundation\BinaryFileResponse::class);
        });

        it('generates filename with model name and timestamp', function (): void {
            createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(PostDataTable::class)
                ->call('loadData');

            $response = $component->instance()->export(['title']);

            $disposition = $response->headers->get('content-disposition');

            expect($disposition)->toContain('Post_')
                ->toContain('.xlsx');
        });

        it('filters out empty columns', function (): void {
            createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(PostDataTable::class)
                ->call('loadData');

            // Passing columns with some falsy values - array_filter removes empties
            $response = $component->instance()->export(['title', '', 'content']);

            expect($response)->toBeInstanceOf(Symfony\Component\HttpFoundation\BinaryFileResponse::class);
        });

        it('exports with no data returns valid response', function (): void {
            $component = Livewire::test(PostDataTable::class)
                ->call('loadData');

            $response = $component->instance()->export(['title', 'content']);

            expect($response)->toBeInstanceOf(Symfony\Component\HttpFoundation\BinaryFileResponse::class);
        });

        it('can be called directly on the component instance', function (): void {
            createTestPost(['user_id' => $this->user->getKey()]);

            $component = Livewire::test(PostDataTable::class)
                ->call('loadData');

            $response = $component->instance()->export(['title', 'content']);

            expect($response)->toBeInstanceOf(Symfony\Component\HttpFoundation\BinaryFileResponse::class);
        });
    });

    describe('isExportable in view data', function (): void {
        it('passes isExportable to view', function (): void {
            $component = Livewire::test(PostDataTable::class);

            $viewData = $component->instance()->getIslandData();

            expect($viewData)->toHaveKey('isExportable')
                ->and($viewData['isExportable'])->toBeTrue();
        });

        it('passes false isExportable to view when disabled', function (): void {
            $component = Livewire::test(NonExportablePostDataTable::class);

            $viewData = $component->instance()->getIslandData();

            expect($viewData)->toHaveKey('isExportable')
                ->and($viewData['isExportable'])->toBeFalse();
        });
    });
});
