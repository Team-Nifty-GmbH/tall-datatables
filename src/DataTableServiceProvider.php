<?php

namespace TeamNiftyGmbH\DataTable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\Builder;
use Spatie\ModelInfo\Attributes\Attribute;
use TeamNiftyGmbH\DataTable\Commands\MakeDataTableCommand;
use TeamNiftyGmbH\DataTable\Commands\ModelInfoCache;
use TeamNiftyGmbH\DataTable\Commands\ModelInfoCacheReset;
use TeamNiftyGmbH\DataTable\Contracts\HasFrontendFormatter;
use TeamNiftyGmbH\DataTable\Helpers\DataTableBladeDirectives;
use TeamNiftyGmbH\DataTable\Helpers\DataTableTagCompiler;

class DataTableServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
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

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->offerPublishing();

        $this->commands([
            MakeDataTableCommand::class,
            ModelInfoCache::class,
            ModelInfoCacheReset::class,
        ]);

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'tall-datatables');

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }

    protected function registerTagCompiler(): void
    {
        Blade::precompiler(static function (string $string): string {
            return app(DataTableTagCompiler::class)->compile($string);
        });
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

        if (! Builder::hasMacro('getScoutResults')) {
            Builder::macro('getScoutResults',
                function (array $highlight = ['*'], int $perPage = 20, int $page = 0) {
                    /** @var Builder $this **/
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

        if (! Builder::hasMacro('toQueryBuilder')) {
            Builder::macro('toQueryBuilder',
                function (array $highlight = ['*'], int $perPage = 20, int $page = 0) {
                    /** @var Builder $this **/
                    $searchResult = $this->getScoutResults($highlight, $perPage, $page);

                    return DB::table($this->model->getTable())
                        ->whereIn($this->model->getKeyName(), $searchResult['ids'])
                        ->tap(function ($builder) use ($searchResult) {
                            $builder->hits = $searchResult['hits'];
                            $builder->scout_pagination = $searchResult['searchResult'];
                        });
                });
        }

        if (! Builder::hasMacro('toEloquentBuilder')) {
            Builder::macro('toEloquentBuilder',
                function (array $highlight = ['*'], int $perPage = 20, int $page = 0) {
                    /** @var Builder $this **/
                    $searchResult = $this->getScoutResults($highlight, $perPage, $page);

                    return $this->model::query()
                        ->whereIn($this->model->getKeyName(), $searchResult['ids'])
                        ->tap(function ($builder) use ($searchResult) {
                            $builder->hits = $searchResult['hits'];
                            $builder->scout_pagination = $searchResult['searchResult'];
                        });
                }
            );
        }

        if (! Attribute::hasMacro('getFormatterType')) {
            Attribute::macro('getFormatterType',
                function (Model|string $model): string|array {
                    $modelInstance = is_string($model) ? new $model() : $model;

                    if (in_array($this->cast, ['accessor', 'attribute']) && $modelInstance->hasCast($this->name)) {
                        $this->cast = $modelInstance->getCasts()[$this->name];
                    } elseif (in_array($this->cast, ['accessor', 'attribute']) && class_exists($this->phpType)) {
                        $this->cast = $this->phpType;
                    }

                    if (
                        class_exists($this->cast ?? false)
                        && in_array(HasFrontendFormatter::class, class_implements($this->cast))
                    ) {
                        return $this->cast::getFrontendFormatter(modelClass: $model);
                    }

                    return strtolower(class_basename($this->cast ?? $this->phpType));
                }
            );
        }
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
}
