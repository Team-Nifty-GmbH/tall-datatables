<?php

namespace TeamNiftyGmbH\DataTable\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Laravel\Scout\Searchable;
use TeamNiftyGmbH\DataTable\Contracts\InteractsWithDataTables;
use Throwable;

class SearchController extends Controller
{
    public function __invoke(Request $request, string $model): mixed
    {
        $model = str_replace('/', '\\', $model);

        if (! class_exists($model) || ! in_array(Searchable::class, class_uses_recursive($model))) {
            abort(404);
        }

        Event::dispatch('tall-datatables-searching', $request);

        /** @var Builder $query */
        if ($request->has('selected')) {
            $selected = $request->get('selected');
            $optionValue = $request->get('option-value') ?: app($model)->getKeyName();

            $query = $model::query();
            is_array($selected)
                ? $query->whereIn($optionValue, $selected)
                : $query->where($optionValue, $selected);
        } elseif ($request->has('search')) {
            $query = ! is_string($request->get('search'))
                ? $model::query()->limit(20)
                : $model::search($request->get('search'))->toEloquentBuilder();
        } else {
            $query = $model::query();
        }

        if ($request->has('with')) {
            $modelInstance = app($model);
            $requestedWith = $request->get('with');
            $relations = is_array($requestedWith) ? $requestedWith : [$requestedWith];

            $validRelations = array_filter($relations, function (string $relation) use ($modelInstance): bool {
                $relationName = explode('.', $relation)[0];

                if (! method_exists($modelInstance, $relationName)) {
                    return false;
                }

                try {
                    return $modelInstance->{$relationName}() instanceof Relation;
                } catch (Throwable) {
                    return false;
                }
            });

            if (! empty($validRelations)) {
                $query->with(is_array($requestedWith) ? $validRelations : $validRelations[0]);
            }
        }

        if ($request->has('limit')) {
            $query->limit($request->get('limit'));
        } else {
            $query->limit(10);
        }

        if ($request->has('orderBy')) {
            $orderByColumn = $request->get('orderBy');
            $table = app($model)->getTable();

            if (Schema::hasColumn($table, $orderByColumn)) {
                $direction = in_array(strtolower($request->get('orderDirection', 'asc')), ['asc', 'desc'])
                    ? $request->get('orderDirection', 'asc')
                    : 'asc';
                $query->orderBy($orderByColumn, $direction);
            }
        }

        if ($request->has('where')) {
            $query->where($request->get('where'));
        }

        if ($request->has('whereIn')) {
            $query->whereIn($request->get('whereIn'));
        }

        if ($request->has('whereNotIn')) {
            $query->whereNotIn($request->get('whereNotIn'));
        }

        if ($request->has('whereNull')) {
            $query->whereNull($request->get('whereNull'));
        }

        if ($request->has('whereNotNull')) {
            $query->whereNotNull($request->get('whereNotNull'));
        }

        if ($request->has('whereBetween')) {
            $query->whereBetween($request->get('whereBetween'));
        }

        if ($request->has('whereNotBetween')) {
            $query->whereNotBetween($request->get('whereNotBetween'));
        }

        if ($request->has('whereDate')) {
            $query->whereDate($request->get('whereDate'));
        }

        if ($request->has('whereMonth')) {
            $query->whereMonth($request->get('whereMonth'));
        }

        if ($request->has('whereDay')) {
            $query->whereDay($request->get('whereDay'));
        }

        if ($request->has('whereYear')) {
            $query->whereYear($request->get('whereYear'));
        }

        if ($request->has('whereTime')) {
            $query->whereTime($request->get('whereTime'));
        }

        if ($request->has('fields')) {
            $fields = $request->get('fields');

            if (is_array($fields)) {
                $table = app($model)->getTable();
                $fields = array_filter($fields, fn (string $field) => Schema::hasColumn($table, $field));
            }

            if (! empty($fields)) {
                $query->select($fields);
            }
        }

        if ($request->has('whereDoesntHave')) {
            $relation = $request->get('whereDoesntHave');
            $modelInstance = app($model);

            if (is_string($relation) && method_exists($modelInstance, $relation)) {
                try {
                    if ($modelInstance->{$relation}() instanceof Relation) {
                        $query->whereDoesntHave($relation);
                    }
                } catch (Throwable) {
                    // Invalid relation — skip silently
                }
            }
        }

        if ($request->has('whereHas')) {
            $relation = $request->get('whereHas');
            $modelInstance = app($model);

            if (is_string($relation) && method_exists($modelInstance, $relation)) {
                try {
                    if ($modelInstance->{$relation}() instanceof Relation) {
                        $query->whereHas($relation);
                    }
                } catch (Throwable) {
                    // Invalid relation — skip silently
                }
            }
        }

        $result = $query->get();

        if ($request->has('appends')) {
            $result->each(function ($item) use ($request): void {
                $item->append($request->get('appends'));
            });
        }

        if (in_array(InteractsWithDataTables::class, class_implements($model))) {
            $result = $result->map(function ($item) use ($request) {
                return array_merge(
                    [
                        'id' => $item->getKey(),
                        'label' => $item->getLabel(),
                        'description' => $item->getDescription(),
                        'src' => $item->getAvatarUrl(),
                    ],
                    $item->only($request->get('fields', [])),
                    $item->only($request->get('appends', []))
                );
            });
        }

        Event::dispatch('tall-datatables-searched', [$request, $result]);

        return $result;
    }
}
