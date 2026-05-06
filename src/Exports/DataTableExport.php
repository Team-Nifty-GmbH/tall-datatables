<?php

namespace TeamNiftyGmbH\DataTable\Exports;

use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TeamNiftyGmbH\DataTable\Exports\Concerns\ExportsData;

class DataTableExport
{
    use ExportsData;

    private const CHUNK_SIZE = 1000;

    private const XLSX_CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

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

        $this->builder->chunk(self::CHUNK_SIZE, function ($rows) use ($sheet, &$rowIndex): void {
            foreach ($rows as $row) {
                $sheet->fromArray([array_values($this->mapRow($row))], null, 'A' . $rowIndex, true);
                $rowIndex++;
            }
        });
    }

    private function writeXlsxTo(string $target): void
    {
        // Spool PhpSpreadsheet's cell collection to disk so a multi-thousand
        // row export does not have to fit every Cell object in PHP memory.
        $previousCache = Settings::getCache();
        $cacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tdt-export-' . bin2hex(random_bytes(8));
        $cache = new Repository(new FileStore(new Filesystem(), $cacheDirectory));
        Settings::setCache($cache);

        $spreadsheet = new Spreadsheet();

        try {
            $sheet = $spreadsheet->getActiveSheet();

            $this->writeHeadings($sheet);
            $this->writeRows($sheet);

            $writer = new Xlsx($spreadsheet);
            $writer->save($target);
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            $cache->clear();
            Settings::setCache($previousCache);
            @rmdir($cacheDirectory);
        }
    }
}
