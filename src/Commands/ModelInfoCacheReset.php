<?php

namespace TeamNiftyGmbH\DataTable\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ModelInfoCacheReset extends Command
{
    protected $description = 'Reset the model info cache';

    protected $signature = 'model-info:cache-reset';

    public function handle(): void
    {
        if (
            Cache::forget(config('tall-datatables.cache_key') . '.modelFinder')
            && Cache::forget(config('tall-datatables.cache_key') . '.modelInfo')
        ) {
            $this->info('Model info cache flushed.');
        } else {
            $this->error('Unable to flush cache.');
        }
    }
}
