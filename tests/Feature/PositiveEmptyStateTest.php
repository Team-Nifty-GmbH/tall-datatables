<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\PositiveEmptyPostDataTable;
use Tests\Fixtures\Livewire\PostDataTable;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

describe('Positive Empty State', function (): void {
    it('shows sad face icon by default when table is empty', function (): void {
        Livewire::test(PostDataTable::class)
            ->call('loadData')
            ->assertSeeHtml('16.318A4.486');
    });

    it('shows happy face icon when positiveEmptyState is true and table is empty', function (): void {
        Livewire::test(PositiveEmptyPostDataTable::class)
            ->call('loadData')
            ->assertSeeHtml('15.182a4.5 4.5 0 0 1-6.364')
            ->assertDontSeeHtml('16.318A4.486');
    });

    it('shows positive text when positiveEmptyState is true', function (): void {
        Livewire::test(PositiveEmptyPostDataTable::class)
            ->call('loadData')
            ->assertSee(__('All clear!'));
    });

    it('shows default text when positiveEmptyState is false', function (): void {
        Livewire::test(PostDataTable::class)
            ->call('loadData')
            ->assertSee(__('No data found'));
    });

    it('defaults positiveEmptyState to false', function (): void {
        $component = Livewire::test(PostDataTable::class);
        expect($component->get('positiveEmptyState'))->toBeFalse();
    });
});
