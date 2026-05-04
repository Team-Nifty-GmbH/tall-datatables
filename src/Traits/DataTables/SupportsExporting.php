<?php

namespace TeamNiftyGmbH\DataTable\Traits\DataTables;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TeamNiftyGmbH\DataTable\Exports\CsvExport;
use TeamNiftyGmbH\DataTable\Exports\DataTableExport;
use TeamNiftyGmbH\DataTable\Exports\JsonExport;
use TeamNiftyGmbH\DataTable\Formatters\FormatterRegistry;

trait SupportsExporting
{
    public array $exportColumns = [];

    /**
     * If set to false the table will not show the export tab in the sidebar.
     */
    #[Locked]
    public bool $isExportable = true;

    #[Renderless]
    public function export(array $columns = [], string $format = 'xlsx', bool $formatted = true): Response|BinaryFileResponse|StreamedResponse
    {
        $query = $this->buildSearch();
        $columns = array_filter($columns) ?: $this->enabledCols;
        $basename = class_basename($this->getModel()) . '_' . now()->toDateTimeLocalString('minute');

        $formatters = [];
        if ($formatted) {
            $model = app($this->getModel());
            $formatters = $this->resolveExportFormatters($model, $columns);
        }

        return match ($format) {
            'csv' => (new CsvExport($query, $columns, $formatters))->download($basename . '.csv'),
            'json' => (new JsonExport($query, $columns, $formatters))->download($basename . '.json'),
            default => (new DataTableExport($query, $columns, $formatters))->download($basename . '.xlsx'),
        };
    }

    #[Renderless]
    public function getExportableColumns(): array
    {
        return array_unique(array_merge($this->availableCols, $this->enabledCols));
    }

    protected function resolveExportFormatters(Model $model, array $columns): array
    {
        $registry = app(FormatterRegistry::class);
        $customFormatters = $this->getFormatters();
        $modelCasts = $model->getCasts();
        $formatters = [];

        foreach ($columns as $col) {
            $baseCol = str_contains($col, '.') ? last(explode('.', $col)) : $col;

            if (isset($customFormatters[$col]) && is_string($customFormatters[$col])) {
                $formatters[$col] = $registry->resolve($customFormatters[$col]);
            } elseif (isset($customFormatters[$col]) && is_array($customFormatters[$col])) {
                $formatterName = $customFormatters[$col][0] ?? 'string';
                $formatterOptions = $customFormatters[$col][1] ?? [];
                $formatters[$col] = $registry->resolveWithOptions($formatterName, $formatterOptions);
            } else {
                $casts = str_contains($col, '.')
                    ? $this->resolveCastsForColumn($model, $col)
                    : $modelCasts;

                $castValue = $casts[$baseCol] ?? null;

                if (is_array($castValue) && count($castValue) >= 1) {
                    $formatters[$col] = $registry->resolveWithOptions($castValue[0] ?? 'string', $castValue[1] ?? []);
                } else {
                    $stringCasts = array_filter($casts, 'is_string');
                    $formatters[$col] = $registry->resolveForColumn($baseCol, $stringCasts);
                }
            }
        }

        return $formatters;
    }
}
