<?php

namespace TeamNiftyGmbH\DataTable\Traits\DataTables;

use Illuminate\Http\Response;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TeamNiftyGmbH\DataTable\Exports\CsvExport;
use TeamNiftyGmbH\DataTable\Exports\DataTableExport;
use TeamNiftyGmbH\DataTable\Exports\JsonExport;

trait SupportsExporting
{
    public array $exportColumns = [];

    /**
     * If set to false the table will not show the export tab in the sidebar.
     */
    #[Locked]
    public bool $isExportable = true;

    #[Renderless]
    public function export(array $columns = [], string $format = 'xlsx'): Response|BinaryFileResponse|StreamedResponse
    {
        $query = $this->buildSearch();
        $columns = array_filter($columns);
        $basename = class_basename($this->getModel()) . '_' . now()->toDateTimeLocalString('minute');

        return match ($format) {
            'csv' => (new CsvExport($query, $columns))->download($basename . '.csv'),
            'json' => (new JsonExport($query, $columns))->download($basename . '.json'),
            default => (new DataTableExport($query, $columns))->download($basename . '.xlsx'),
        };
    }

    #[Renderless]
    public function getExportableColumns(): array
    {
        return array_unique(array_merge($this->availableCols, $this->enabledCols));
    }
}
