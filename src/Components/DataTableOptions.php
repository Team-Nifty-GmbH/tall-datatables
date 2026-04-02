<?php

namespace TeamNiftyGmbH\DataTable\Components;

use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Component;
use TeamNiftyGmbH\DataTable\DataTable;

#[Lazy]
class DataTableOptions extends Component
{
    #[Locked]
    public array $aggregatable = [];

    public array $aggregations = [];

    #[Locked]
    public array $availableCols = [];

    public array $enabledCols = [];

    public ?string $groupBy = null;

    #[Locked]
    public bool $isExportable = false;

    public function render(): \Illuminate\View\View
    {
        return view('tall-datatables::livewire.options-v2');
    }

    public function reorderColumns(array $columns): void
    {
        $this->enabledCols = $columns;
        $this->dispatch('options-changed', options: ['enabledCols' => $this->enabledCols])->to(DataTable::class);
    }

    public function setAggregation(string $column, ?string $function): void
    {
        if ($function) {
            $this->aggregations[$column] = $function;
        } else {
            unset($this->aggregations[$column]);
        }

        $this->dispatch('options-changed', options: ['aggregations' => $this->aggregations])->to(DataTable::class);
    }

    public function setGroupBy(?string $column): void
    {
        $this->groupBy = $column;
        $this->dispatch('options-changed', options: ['groupBy' => $this->groupBy])->to(DataTable::class);
    }

    public function toggleColumn(string $column): void
    {
        if (in_array($column, $this->enabledCols)) {
            $this->enabledCols = array_values(array_diff($this->enabledCols, [$column]));
        } else {
            $this->enabledCols[] = $column;
        }

        $this->dispatch('options-changed', options: ['enabledCols' => $this->enabledCols])->to(DataTable::class);
    }
}
