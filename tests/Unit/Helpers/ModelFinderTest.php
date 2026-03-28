<?php

use Illuminate\Support\Collection;
use TeamNiftyGmbH\DataTable\Helpers\ModelFinder;

describe('ModelFinder', function (): void {
    test('all returns a Collection', function (): void {
        $result = ModelFinder::all();

        expect($result)->toBeInstanceOf(Collection::class);
    });
});
