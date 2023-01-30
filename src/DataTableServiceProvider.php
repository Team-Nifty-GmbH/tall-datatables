<?php

namespace TeamNiftyGmbH\DataTable;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use TeamNiftyGmbH\DataTable\Commands\MakeDataTableCommand;

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
            ->hasMigration('create_tall-datatables_table')
            ->hasCommand(MakeDataTableCommand::class);
    }
}
