<?php

namespace TeamNiftyGmbH\DataTable;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\Builder;
use Livewire\Livewire;
use TallStackUi\Facades\TallStackUi;
use TeamNiftyGmbH\DataTable\Commands\MakeDataTableCommand;
use TeamNiftyGmbH\DataTable\Commands\ModelInfoCache;
use TeamNiftyGmbH\DataTable\Commands\ModelInfoCacheReset;
use TeamNiftyGmbH\DataTable\Components\DataTableFilters;
use TeamNiftyGmbH\DataTable\Components\DataTableOptions as DataTableOptionsV2;
use TeamNiftyGmbH\DataTable\Formatters\FormatterRegistry;
use TeamNiftyGmbH\DataTable\Helpers\DataTableBladeDirectives;
use TeamNiftyGmbH\DataTable\Helpers\DataTableTagCompiler;
use TeamNiftyGmbH\DataTable\Helpers\ModelInfo;
use TeamNiftyGmbH\DataTable\Livewire\Options;

class DataTableServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        // Options is now a Blade component rendered directly inside DataTable, not a separate Livewire component
        Livewire::component('data-table-filters', DataTableFilters::class);
        Livewire::component('data-table-options-v2', DataTableOptionsV2::class);
        $this->offerPublishing();

        $this->app['events']->listen('Laravel\Octane\Events\RequestTerminated', function (): void {
            ModelInfo::flush();
        });

        $this->commands([
            MakeDataTableCommand::class,
            ModelInfoCache::class,
            ModelInfoCacheReset::class,
        ]);

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'tall-datatables');

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        if (method_exists(TallStackUi::class, 'customize')) {
            TallStackUi::customize()
                ->tab('datatable')
                ->block('item.wrapper', 'inline-flex items-center gap-2 whitespace-nowrap px-3 py-2 text-sm transition-all')
                ->block('base.content', 'text-secondary-700 dark:text-dark-300 px-0 py-3')
                ->block('base.wrapper', 'w-full');
        }
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton(FormatterRegistry::class);

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
                            'attributesToHighlight' => array_values($highlight),
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
                    $ids = data_get($searchResult, 'ids', []);

                    $query = DB::table($this->model->getTable())
                        ->whereIn($this->model->getTable() . '.' . $this->model->getKeyName(), $ids);

                    if ($ids) {
                        $query->orderByRaw('FIELD(' . $this->model->getKeyName() . ', '
                            . implode(',', $ids) . ')');
                    }

                    return $query->tap(function (QueryBuilder $builder) use ($searchResult): void {
                        $builder->hits = data_get($searchResult, 'hits');
                        $builder->scout_pagination = data_get($searchResult, 'searchResult');
                    });
                });
        }

        if (class_exists(Builder::class) && ! Builder::hasMacro('toEloquentBuilder')) {
            Builder::macro('toEloquentBuilder',
                function (array $highlight = ['*'], int $perPage = 20, int $page = 0) {
                    /** @var Builder $this */
                    $searchResult = $this->getScoutResults($highlight, $perPage, $page);
                    $ids = data_get($searchResult, 'ids', []);

                    $query = $this->model::query()->whereKey($ids);

                    if ($ids) {
                        $query->orderByRaw('FIELD(' . $this->model->getKeyName() . ', '
                            . implode(',', $ids) . ')');
                    }

                    return $query->tap(function (EloquentBuilder $builder) use ($searchResult): void {
                        $builder->hits = data_get($searchResult, 'hits');
                        $builder->scout_pagination = data_get($searchResult, 'searchResult');
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
