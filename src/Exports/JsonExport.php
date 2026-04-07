<?php

namespace TeamNiftyGmbH\DataTable\Exports;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TeamNiftyGmbH\DataTable\Exports\Concerns\ExportsData;

class JsonExport
{
    use ExportsData;

    public function __construct(
        private EloquentBuilder $builder,
        array $exportColumns = [],
    ) {
        $this->exportColumns = $exportColumns;
    }

    public function download(string $filename): StreamedResponse
    {
        $response = new StreamedResponse(function (): void {
            echo '[';

            $first = true;
            $this->builder->chunk(1000, function ($rows) use (&$first): void {
                foreach ($rows as $row) {
                    if (! $first) {
                        echo ',';
                    }
                    $first = false;

                    $flat = $this->mapRow($row);
                    $nested = [];
                    foreach ($flat as $key => $value) {
                        data_set($nested, $key, $value);
                    }

                    echo json_encode($nested, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                }
            });

            echo ']';
        });

        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}
