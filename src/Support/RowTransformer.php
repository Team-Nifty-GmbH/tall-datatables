<?php

namespace TeamNiftyGmbH\DataTable\Support;

use Illuminate\Database\Eloquent\Model;
use TeamNiftyGmbH\DataTable\Formatters\FormatterRegistry;
use Throwable;

class RowTransformer
{
    public function __construct(protected FormatterRegistry $registry) {}

    /**
     * Transform a model into an array of column cells with raw and optional display values.
     *
     * @param  array<int, string>  $enabledCols
     * @return array<string, array{raw: mixed, display?: string}>
     */
    public function transform(Model $model, array $enabledCols): array
    {
        $context = $model->attributesToArray();
        $row = [];

        foreach ($enabledCols as $col) {
            $raw = data_get($model, $col);
            $colCasts = $this->resolveCasts($model, $col);
            $formatter = $this->registry->resolveForColumn(
                $this->baseColumn($col),
                $colCasts,
            );

            $display = $formatter->format($raw, $context);
            $rawString = is_null($raw) ? '' : (string) $raw;

            // Omit display when it equals escaped raw to save payload
            if ($display === e($rawString)) {
                $row[$col] = ['raw' => $raw];
            } else {
                $row[$col] = ['raw' => $raw, 'display' => $display];
            }
        }

        return $row;
    }

    /**
     * For relation columns, resolve casts from the related model.
     *
     * @return array<string, string>
     */
    protected function resolveCasts(Model $model, string $col): array
    {
        if (! str_contains($col, '.')) {
            return $model->getCasts();
        }

        $parts = explode('.', $col);
        array_pop($parts);
        $relation = implode('.', $parts);

        try {
            $related = $model;
            foreach (explode('.', $relation) as $segment) {
                $related = $related->{$segment}()->getRelated();
            }

            return $related->getCasts();
        } catch (Throwable) {
            return [];
        }
    }

    protected function baseColumn(string $col): string
    {
        return str_contains($col, '.') ? last(explode('.', $col)) : $col;
    }
}
