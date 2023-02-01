<?php

namespace TeamNiftyGmbH\DataTable\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ModelInfoCacheReset extends Command
{
    protected $signature = 'model-info:cache-reset';

    protected $description = 'Reset the model info cache';

    public function handle()
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
