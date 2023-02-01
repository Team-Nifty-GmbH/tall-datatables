<?php

namespace TeamNiftyGmbH\DataTable\Commands;

use Illuminate\Console\Command;
use TeamNiftyGmbH\DataTable\Helpers\ModelInfo;

class ModelInfoCache extends Command
{
    protected $signature = 'model-info:cache';

    protected $description = 'Reset the model info cache';

    public function handle()
    {
        $this->call(ModelInfoCacheReset::class);

        if (ModelInfo::forAllModels()->count()) {
            $this->info('Model info cached.');
        } else {
            $this->error('Unable to cache Model info.');
        }
    }
}
