<?php

namespace TeamNiftyGmbH\DataTable\Exports;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TeamNiftyGmbH\DataTable\Exports\Concerns\ExportsData;

class CsvExport
{
    use ExportsData;

    public function __construct(
        private EloquentBuilder $builder,
        array $exportColumns = [],
        array $formatters = [],
    ) {
        $this->exportColumns = $exportColumns;
        $this->exportFormatters = $formatters;
    }

    public function download(string $filename): StreamedResponse
    {
        $response = new StreamedResponse(function (): void {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, $this->headings(), ';');

            // Data rows
            $this->builder->chunk(1000, function ($rows) use ($handle): void {
                foreach ($rows as $row) {
                    fputcsv($handle, array_values($this->mapRow($row)), ';');
                }
            });

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}
