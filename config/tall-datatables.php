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

    'data_table_namespace' => 'App\\Http\\Livewire\\DataTables',

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
];
