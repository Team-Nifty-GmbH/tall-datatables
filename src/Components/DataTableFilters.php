<?php

namespace TeamNiftyGmbH\DataTable\Components;

use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Component;
use TeamNiftyGmbH\DataTable\DataTable;
use TeamNiftyGmbH\DataTable\Support\ColumnResolver;

#[Lazy]
class DataTableFilters extends Component
{
    #[Locked]
    public array $availableCols = [];

    #[Locked]
    public string $cacheKey = '';

    public array $filters = [];

    #[Locked]
    public string $model = '';

    public array $savedFilters = [];

    public function render(): \Illuminate\View\View
    {
        return view('tall-datatables::livewire.filters');
    }

    public function addFilter(array $filter): void
    {
        $this->filters[] = $filter;
        $this->dispatch('filters-changed', filters: $this->filters)->to(DataTable::class);
    }

    public function clearFilters(): void
    {
        $this->filters = [];
        $this->dispatch('filters-changed', filters: $this->filters)->to(DataTable::class);
    }

    public function getColumnType(string $column): string
    {
        return (new ColumnResolver($this->model))->getInputType($column);
    }

    public function removeFilter(int $index): void
    {
        unset($this->filters[$index]);
        $this->filters = array_values($this->filters);
        $this->dispatch('filters-changed', filters: $this->filters)->to(DataTable::class);
    }
}
