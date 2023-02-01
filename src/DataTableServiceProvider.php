<?php

namespace TeamNiftyGmbH\DataTable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Builder;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\ModelInfo\Attributes\Attribute;
use TeamNiftyGmbH\DataTable\Commands\MakeDataTableCommand;
use TeamNiftyGmbH\DataTable\Commands\ModelInfoCache;
use TeamNiftyGmbH\DataTable\Commands\ModelInfoCacheReset;
use TeamNiftyGmbH\DataTable\Contracts\HasFrontendFormatter;
use TeamNiftyGmbH\DataTable\Helpers\DataTableBladeDirectives;
use TeamNiftyGmbH\DataTable\Helpers\DataTableTagCompiler;

class DataTableServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('tall-datatables')
            ->hasConfigFile()
            ->hasViews()
            ->hasRoute('web')
            ->hasMigration('create_datatable_user_settings_table')
            ->runsMigrations()
            ->hasCommands([
                MakeDataTableCommand::class,
                ModelInfoCache::class,
                ModelInfoCacheReset::class,
            ]);
    }

    public function bootingPackage()
    {
        $this->registerBladeDirectives();
        $this->registerTagCompiler();
        $this->registerMacros();
    }

    protected function registerTagCompiler()
    {
        Blade::precompiler(static function (string $string): string {
            return app(DataTableTagCompiler::class)->compile($string);
        });
    }

    protected function registerBladeDirectives()
    {
        Blade::directive('dataTablesScripts', static function (?string $attributes = ''): string {
            if (! $attributes) {
                $attributes = '[]';
            }

            return (new DataTableBladeDirectives())->scripts($attributes);
        });

        Blade::directive('dataTableStyles', static function (): string {
            return (new DataTableBladeDirectives())->styles();
        });
    }

    protected function registerMacros(): void
    {
        if (! Builder::hasMacro('toQueryBuilder')) {
            Builder::macro('toQueryBuilder',
                function (array $highlight = ['*'], int $perPage = 20, int $page = 0) {
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

        if (! Builder::hasMacro('getScoutResults')) {
            Builder::macro('getScoutResults',
                function (array $highlight = ['*'], int $perPage = 20, int $page = 0) {
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
}
