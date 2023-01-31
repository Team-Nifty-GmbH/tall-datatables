<?php

namespace TeamNiftyGmbH\DataTable\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Livewire\Commands\ComponentParser;
use Livewire\Commands\MakeCommand as LivewireMakeCommand;
use Spatie\ModelInfo\ModelFinder;

class MakeDataTableCommand extends Command
{
    protected ComponentParser $parser;

    protected string $model;

    protected string $stubDirectory = 'vendor/team-nifty-gmbh/tall-datatables/stubs';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:data-table
        {name}
        {model : The name of the model you want to use in this table}
        {--force}
        {--stub}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Livewire DataTable component';

    /**
     * Execute the console command.
     */
    public function handle()
    {
//        $stubSubDirectory = $this->option('stub') ?? $this->stubDirectory;
//        $this->stubDirectory = rtrim('stubs' . DIRECTORY_SEPARATOR . $stubSubDirectory, DIRECTORY_SEPARATOR);

        $this->parser = new ComponentParser(
            config('tall-datatables.data_table_namespace'),
            config('tall-datatables.view_path'),
            $this->argument('name'),
        );

        $livewireMakeCommand = new LivewireMakeCommand();

        if ($livewireMakeCommand->isReservedClassName($name = $this->parser->className())) {
            $this->line("<fg=red;options=bold>Class is reserved:</> {$name}");

            return;
        }

        $this->model = ModelFinder::all()
            ->filter(
                function ($modelInfo) {
                    return class_basename($modelInfo) === $this->argument('model')
                        || $modelInfo === $this->argument('model');
                }
            )
            ?->first();
        $force = $this->option('force');

        if ($classPath = $this->createClass($force)) {
            $this->info('Livewire Datatable Created: ' . $classPath);
        }
    }

    /**
     * @param bool $force
     * @return string|bool
     */
    protected function createClass(bool $force = false): string|bool
    {
        $classPath = $this->parser->classPath();

        if (! $force && File::exists($classPath)) {
            $this->line("<fg=red;options=bold>Class already exists:</> {$this->parser->relativeClassPath()}");

            return false;
        }

        $this->ensureDirectoryExists($classPath);

        File::put($classPath, $this->classContents());

        return $classPath;
    }

    /**
     * @param string $path
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (! File::isDirectory(dirname($path))) {
            File::makeDirectory(dirname($path), 0777, true, true);
        }
    }

    /**
     * @return string
     */
    private function classContents(): string
    {
        return str_replace(
            ['[namespace]', '[class]', '[model]', '[model_import]', '[columns]'],
            [$this->parser->classNamespace(), $this->parser->className(), class_basename($this->model), $this->model],
            file_get_contents(base_path($this->stubDirectory . DIRECTORY_SEPARATOR . 'livewire.data-table.stub'))
        );
    }
}
