<?php

namespace TeamNiftyGmbH\DataTable\Exports;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TeamNiftyGmbH\DataTable\Exports\Concerns\ExportsData;

class JsonExport
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
            $this->writeJsonTo('php://output');
        });

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
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
            $this->writeJsonTo($tempFile);

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

    private function writeJsonTo(string $target): void
    {
        $handle = fopen($target, 'w');

        try {
            fwrite($handle, '[');

            $first = true;
            $this->builder->chunk(self::CHUNK_SIZE, function ($rows) use ($handle, &$first): void {
                foreach ($rows as $row) {
                    if (! $first) {
                        fwrite($handle, ',');
                    }
                    $first = false;

                    $flat = $this->mapRow($row);
                    $nested = [];
                    foreach ($flat as $key => $value) {
                        data_set($nested, $key, $value);
                    }

                    fwrite($handle, json_encode($nested, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            });

            fwrite($handle, ']');
        } finally {
            fclose($handle);
        }
    }
}
