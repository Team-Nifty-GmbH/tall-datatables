<?php

use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Fixtures\Livewire\NonExportablePostDataTable;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('Export Functionality', function (): void {
    test('datatable renders without export button when not exportable', function (): void {
        $component = Livewire::test(NonExportablePostDataTable::class);

        $component->assertDontSeeHtml('>Export<')
            ->assertDontSeeHtml('>Exportieren<');
    });

    test('falls back to enabledCols when called with empty columns', function (): void {
        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'Fallback Title',
            'content' => 'Fallback Content',
            'price' => '12.50',
            'is_published' => true,
        ]);

        $component = Livewire::test(PostDataTable::class)->instance();

        $response = $component->export([], 'xlsx', false);

        ob_start();
        $response->sendContent();
        $body = ob_get_clean();

        $tmp = tempnam(sys_get_temp_dir(), 'tdt-test-');
        file_put_contents($tmp, $body);

        try {
            $rows = IOFactory::createReaderForFile($tmp)
                ->load($tmp)
                ->getActiveSheet()
                ->toArray();

            expect($rows[0])->toBe([
                __('Title'),
                __('Content'),
                __('Price'),
                __('Is Published'),
                __('Created At'),
            ]);

            expect($rows[1][0])->toBe('Fallback Title')
                ->and($rows[1][1])->toBe('Fallback Content');
        } finally {
            @unlink($tmp);
        }
    });

    test('explicit columns still override enabledCols', function (): void {
        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'Explicit Title',
            'content' => 'Should Not Appear',
        ]);

        $component = Livewire::test(PostDataTable::class)->instance();

        $response = $component->export(['title'], 'xlsx', false);

        ob_start();
        $response->sendContent();
        $body = ob_get_clean();

        $tmp = tempnam(sys_get_temp_dir(), 'tdt-test-');
        file_put_contents($tmp, $body);

        try {
            $rows = IOFactory::createReaderForFile($tmp)
                ->load($tmp)
                ->getActiveSheet()
                ->toArray();

            expect($rows[0])->toBe([__('Title')]);
            expect($rows[1])->toBe(['Explicit Title']);
        } finally {
            @unlink($tmp);
        }
    });
});
