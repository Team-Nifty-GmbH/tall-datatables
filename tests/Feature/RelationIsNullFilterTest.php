<?php

use Livewire\Livewire;
use Tests\Fixtures\Livewire\ProductDataTable;

beforeEach(function (): void {
    $this->actingAs(createTestUser());

    $this->withoutUser = createTestProduct(['user_id' => null]);
    $this->withUser = createTestProduct();
});

function relationIsNullFilterResult(array $filter): array
{
    $component = Livewire::test(ProductDataTable::class);
    $component->set('userFilters', [[$filter]]);
    $component->call('loadData');

    return collect($component->instance()->getDataForTesting()['data'])->pluck('id')->toArray();
}

it('returns records without the relation when filtering a relation column for null', function (): void {
    expect(relationIsNullFilterResult([
        'column' => 'user.name',
        'operator' => 'is null',
        'value' => null,
    ]))->toBe([$this->withoutUser->getKey()]);
});

it('returns records without the relation for a quoted is null text filter', function (): void {
    $component = Livewire::test(ProductDataTable::class);
    $component->set('userFilters', ['text' => ['user.name' => '"is null"']]);
    $component->call('loadData');

    $data = $component->instance()->getDataForTesting();

    expect(collect($data['data'])->pluck('id')->toArray())->toBe([$this->withoutUser->getKey()]);
});

it('keeps records with the relation out of the is not null result only when the relation is missing', function (): void {
    expect(relationIsNullFilterResult([
        'column' => 'user.name',
        'operator' => 'is not null',
        'value' => null,
    ]))->toBe([$this->withUser->getKey()]);
});
