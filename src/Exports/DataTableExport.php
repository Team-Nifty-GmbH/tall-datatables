<?php

namespace TeamNiftyGmbH\DataTable\Exports;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TeamNiftyGmbH\DataTable\Exports\Concerns\ExportsData;

class DataTableExport
{
    use ExportsData;

    private const CHUNK_SIZE = 250;

    private const XLSX_CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    public function __construct(
        private EloquentBuilder $builder,
        array $exportColumns = [],
        array $formatters = [],
        private ?Closure $onChunk = null,
    ) {
        $this->exportColumns = $exportColumns;
        $this->exportFormatters = $formatters;
    }

    public function download(string $filename): StreamedResponse
    {
        $response = new StreamedResponse(function (): void {
            $this->writeXlsxTo('php://output');
        });

        $response->headers->set('Content-Type', self::XLSX_CONTENT_TYPE);
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    public function map($row): array
    {
        return $this->mapRow($row);
    }

    public function query(): Relation|EloquentBuilder|Builder
    {
        return $this->builder;
    }

    public function store(string $path, ?string $disk = null): bool
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'tdt-export-');

        if ($tempFile === false) {
            throw new RuntimeException('Failed to create temporary file for export.');
        }

        try {
            $this->writeXlsxTo($tempFile);

            $stream = fopen($tempFile, 'rb');

            try {
                $storage = $disk ? Storage::disk($disk) : Storage::disk();
                $result = $storage->put($path, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            return (bool) $result;
        } finally {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    private function autoSizeColumns(Worksheet $sheet): void
    {
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
        }
    }

    private function writeHeadings(Worksheet $sheet): void
    {
        $headings = $this->headings();

        if ($headings === []) {
            return;
        }

        $sheet->fromArray([$headings], null, 'A1', true);
    }

    private function writeRows(Worksheet $sheet): void
    {
        $rowIndex = 2;
        $processed = 0;

        $this->builder->chunk(self::CHUNK_SIZE, function ($rows) use ($sheet, &$rowIndex, &$processed): void {
            foreach ($rows as $row) {
                $sheet->fromArray([array_values($this->mapRow($row))], null, 'A' . $rowIndex, true);
                $rowIndex++;
                $processed++;
            }

            if (! is_null($this->onChunk)) {
                ($this->onChunk)($processed);
            }
        });
    }

    private function writeXlsxTo(string $target): void
    {
        $spreadsheet = new Spreadsheet();

        try {
            $sheet = $spreadsheet->getActiveSheet();

            $this->writeHeadings($sheet);
            $this->writeRows($sheet);
            $this->autoSizeColumns($sheet);

            $writer = new Xlsx($spreadsheet);
            $writer->save($target);
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }
}
