<?php

namespace TeamNiftyGmbH\DataTable\Traits\DataTables;

use Illuminate\Http\Response;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use TeamNiftyGmbH\DataTable\Exports\DataTableExport;

trait SupportsExporting
{
    /**
     * If set to false the table will not show the export tab in the sidebar.
     */
    #[Locked]
    public bool $isExportable = true;

    public array $exportColumns = [];

    #[Renderless]
    public function getExportableColumns(): array
    {
        return array_unique(array_merge($this->availableCols, $this->enabledCols));
    }

    #[Renderless]
    public function export(array $columns = []): Response|BinaryFileResponse
    {
        $query = $this->buildSearch();

        return (new DataTableExport($query, array_filter($columns)))
            ->download(class_basename($this->getModel()) . '_' . now()->toDateTimeLocalString('minute') . '.xlsx');
    }
}
