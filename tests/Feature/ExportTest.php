<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\NonExportablePostDataTable;

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
});
