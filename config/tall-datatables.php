<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Data Table Namespace
    |--------------------------------------------------------------------------
    |
    | This is where new data tables will be created.
    |
    */

    'data_table_namespace' => config('livewire.class_namespace') . '\\DataTables',

    'view_path' => resource_path('views/livewire'),

    /*
    |--------------------------------------------------------------------------
    | Cache Key
    |--------------------------------------------------------------------------
    |
    | This is the cache key used to cache the data table models.
    |
    */

    'cache_key' => 'team-nifty.tall-datatables',

    'should_cache' => env('TALL_DATATABLES_CACHE', true),

    /*
    |--------------------------------------------------------------------------
    | Max Relation Column Values
    |--------------------------------------------------------------------------
    |
    | When a to-many relation attribute is enabled as a column (e.g. orders.total),
    | the relation is eager loaded for every row. Without a bound a single row can
    | pull thousands of related records into memory and exhaust the worker. This
    | caps how many related records are loaded per parent for such columns.
    | Set to 0 to disable the cap.
    |
    */

    'max_relation_column_values' => env('TALL_DATATABLES_MAX_RELATION_COLUMN_VALUES', 50),

    /*
    |--------------------------------------------------------------------------
    | Search Route
    |--------------------------------------------------------------------------
    |
    | The search route is used to search for models.
    | You should define your own route and set the name here.
    | This package provides a default controller where you cant point to.
    |
    */

    'search_route' => env('TALL_DATATABLES_SEARCH_ROUTE', ''),

    'models' => [
        'datatable_user_setting' => TeamNiftyGmbH\DataTable\Models\DatatableUserSetting::class,
    ],

    'scout' => [
        /*
        |----------------------------------------------------------------------
        | Ranking Score Threshold
        |----------------------------------------------------------------------
        |
        | When a model uses Meilisearch hybrid (semantic) search, results carry
        | a per-hit relevance score between 0 and 1. Set this to a float in that
        | range to drop hits below the threshold from the results. Leave it
        | null (default) to disable filtering and keep all hits.
        |
        */

        'ranking_score_threshold' => env('TALL_DATATABLES_RANKING_SCORE_THRESHOLD'),
    ],
];
