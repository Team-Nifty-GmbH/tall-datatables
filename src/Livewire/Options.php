<?php

namespace TeamNiftyGmbH\DataTable\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Options extends Component
{
    #[Locked]
    public ?array $aggregatable = null;

    #[Locked]
    public ?bool $allowSoftDeletes = null;

    #[Locked]
    public ?bool $isExportable = null;

    #[Locked]
    public ?bool $isFilterable = null;

    public function render(): View
    {
        return view('tall-datatables::components.options');
    }
}
