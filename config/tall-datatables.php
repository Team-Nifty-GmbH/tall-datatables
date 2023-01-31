<?php

// config for TeamNiftyGmbH/DataTable
return [
    'data_table_namespace' => 'App\\Http\\Livewire\\DataTables',
    'view_path' => resource_path('views/livewire'),
    'cache_key' => 'tall-datatables',
    'search_route' => env('TALL_DATATABLES_SEARCH_ROUTE', ''),
];
