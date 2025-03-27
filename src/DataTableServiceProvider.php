<?php

namespace TeamNiftyGmbH\DataTable;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\Builder;
use Livewire\Livewire;
use TeamNiftyGmbH\DataTable\Commands\MakeDataTableCommand;
use TeamNiftyGmbH\DataTable\Commands\ModelInfoCache;
use TeamNiftyGmbH\DataTable\Commands\ModelInfoCacheReset;
use TeamNiftyGmbH\DataTable\Helpers\DataTableBladeDirectives;
use TeamNiftyGmbH\DataTable\Helpers\DataTableTagCompiler;
use TeamNiftyGmbH\DataTable\Livewire\Options;

class DataTableServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        Livewire::component('tall-datatables::options', Options::class);
        $this->offerPublishing();

        $this->commands([
            MakeDataTableCommand::class,
            ModelInfoCache::class,
            ModelInfoCacheReset::class,
        ]);

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'tall-datatables');

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->registerBladeDirectives();
        $this->registerTagCompiler();
        $this->registerMacros();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->mergeConfigFrom(
            __DIR__ . '/../config/tall-datatables.php',
            'tall-datatables'
        );
    }

    protected function offerPublishing(): void
    {
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'tall-datatables-migrations');

        $this->publishes([
            __DIR__ . '/../config/tall-datatables.php' => config_path('tall-datatables.php'),
        ], 'tall-datatables-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/tall-datatables'),
        ], 'tall-datatables-views');

        $this->publishes([
            __DIR__ . '/../stubs/livewire.data-table.stub' => base_path('stubs/livewire.data-table.stub'),
        ], 'tall-datatables-stub');
    }

    protected function registerBladeDirectives(): void
    {
        Blade::directive('dataTablesScripts', static function (?string $attributes = ''): string {
            if (! $attributes) {
                $attributes = [];
            }

            return (new DataTableBladeDirectives())->scripts(attributes: $attributes);
        });

        Blade::directive('dataTableStyles', static function (): string {
            return (new DataTableBladeDirectives())->styles();
        });
    }

    protected function registerMacros(): void
    {
        if (class_exists(Builder::class) && ! Builder::hasMacro('getScoutResults')) {
            Builder::macro('getScoutResults',
                function (array $highlight = ['*'], int $perPage = 20, int $page = 0) {
                    /** @var Builder $this */
                    $searchResult = $this->options(
                        [
                            'attributesToHighlight' => $highlight,
                            'highlightPreTag' => '<mark>',
                            'highlightPostTag' => '</mark>',
                            'limit' => $perPage,
                            'offset' => max(($page - 1) * $perPage, 0),
                        ])
                        ->raw();

                    $hits = Arr::keyBy(data_get($searchResult, 'hits'), $this->model->getKeyName());
                    unset($searchResult['hits']);

                    $ids = collect($hits)->pluck($this->model->getKeyName())->all();

                    return [
                        'hits' => $hits,
                        'ids' => $ids,
                        'searchResult' => $searchResult,
                    ];
                }
            );
        }

        if (class_exists(Builder::class) && ! Builder::hasMacro('toQueryBuilder')) {
            Builder::macro('toQueryBuilder',
                function (array $highlight = ['*'], int $perPage = 20, int $page = 0) {
                    /** @var Builder $this */
                    $searchResult = $this->getScoutResults($highlight, $perPage, $page);

                    return DB::table($this->model->getTable())
                        ->whereIn($this->model->getTable() . '.' . $this->model->getKeyName(), $searchResult['ids'])
                        ->tap(function ($builder) use ($searchResult): void {
                            $builder->hits = $searchResult['hits'];
                            $builder->scout_pagination = $searchResult['searchResult'];
                        });
                });
        }

        if (class_exists(Builder::class) && ! Builder::hasMacro('toEloquentBuilder')) {
            Builder::macro('toEloquentBuilder',
                function (array $highlight = ['*'], int $perPage = 20, int $page = 0) {
                    /** @var Builder $this */
                    $searchResult = $this->getScoutResults($highlight, $perPage, $page);

                    return $this->model::query()
                        ->whereIn($this->model->getKeyName(), $searchResult['ids'])
                        ->tap(function ($builder) use ($searchResult): void {
                            $builder->hits = $searchResult['hits'];
                            $builder->scout_pagination = $searchResult['searchResult'];
                        });
                }
            );
        }
    }

    protected function registerTagCompiler(): void
    {
        Blade::precompiler(static function (string $string): string {
            return app(DataTableTagCompiler::class)->compile($string);
        });
    }
}
