<?php

namespace TeamNiftyGmbH\DataTable\Commands;

use Illuminate\Console\Command;
use TeamNiftyGmbH\DataTable\Helpers\ModelInfo;

class ModelInfoCache extends Command
{
    protected $description = 'Reset the model info cache';

    protected $signature = 'model-info:cache';

    public function handle(): void
    {
        $this->call(ModelInfoCacheReset::class);

        if (ModelInfo::forAllModels()->count()) {
            $this->info('Model info cached.');
        } else {
            $this->error('Unable to cache Model info.');
        }
    }
}
