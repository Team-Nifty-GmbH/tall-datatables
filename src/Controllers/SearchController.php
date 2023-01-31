<?php

namespace TeamNiftyGmbH\DataTable\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Laravel\Scout\Searchable;

class SearchController extends Controller
{
    public function __invoke(Request $request, $model)
    {
        $model = str_replace('/', '\\', $model);

        if (! class_exists($model) || ! in_array(Searchable::class, class_uses($model))) {
            abort(404);
        }

        if ($request->has('selected')) {
            $selected = $request->get('selected');
            $optionValue = $request->get('option-value') ?: 'id';
            $selected = $request->has('option-value')
                ? Arr::pluck($selected, $optionValue)
                : $selected;

            $query = $model::query();
            is_array($selected)
                ? $query->whereIn($optionValue, $selected)
                : $query->where($optionValue, $selected);
        } else {
            $search = ! is_string($request->search) ? '' : $request->search;
            $query = $model::search($search)
                ->toEloquentBuilder();
        }

        if ($request->has('with')) {
            $query->with($request->get('with'));
        }

        if ($request->has('limit')) {
            $query->limit($request->get('limit'));
        } else {
            $query->limit(10);
        }

        if ($request->has('orderBy')) {
            $query->orderBy($request->get('orderBy'));
        }

        if ($request->has('orderDirection')) {
            $query->orderBy($request->get('orderDirection'));
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
            $query->select($request->get('fields'));
        }

        $result = $query->get();

        if ($request->has('appends')) {
            $result->each(function ($item) use ($request) {
                $item->append($request->get('appends'));
            });
        }

        return $result;
    }
}
