<?php

describe('ModelInfoCache Command', function (): void {
    test('model-info:cache command can be called', function (): void {
        $this->artisan('model-info:cache')
            ->assertSuccessful();
    });
});
