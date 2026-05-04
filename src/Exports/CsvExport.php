<?php

namespace TeamNiftyGmbH\DataTable\Exports;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TeamNiftyGmbH\DataTable\Exports\Concerns\ExportsData;

class CsvExport
{
    use ExportsData;

    private const CHUNK_SIZE = 1000;

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
            $this->writeCsvTo('php://output');
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    public function store(string $path, ?string $disk = null): bool
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'tdt-export-');

        if ($tempFile === false) {
            throw new RuntimeException('Failed to create temporary file for export.');
        }

        try {
            $this->writeCsvTo($tempFile);

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

    private function writeCsvTo(string $target): void
    {
        $handle = fopen($target, 'w');

        try {
            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, $this->headings(), ';');

            // Data rows
            $this->builder->chunk(self::CHUNK_SIZE, function ($rows) use ($handle): void {
                foreach ($rows as $row) {
                    fputcsv($handle, array_values($this->mapRow($row)), ';');
                }
            });
        } finally {
            fclose($handle);
        }
    }
}
