<?php

namespace TeamNiftyGmbH\DataTable\Commands;

use Illuminate\Console\Command;
use TeamNiftyGmbH\DataTable\Helpers\SchemaInfo;

class ModelInfoCache extends Command
{
    protected $description = 'Reset the model info cache';

    protected $signature = 'model-info:cache';

    public function handle(): void
    {
        SchemaInfo::flush();

        $this->info('Model info cache flushed.');
    }
}
