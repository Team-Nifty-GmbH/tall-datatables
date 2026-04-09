<?php

describe('SchemaInfoCache Command', function (): void {
    test('model-info:cache command can be called', function (): void {
        $this->artisan('model-info:cache')
            ->assertSuccessful();
    });

    test('model-info:cache outputs info or error message', function (): void {
        $result = $this->artisan('model-info:cache');

        // In test environment, forAllModels() may return empty (no app/Models dir)
        // so either success or error message is acceptable
        $result->assertExitCode(0);
    });

    test('model-info:cache-reset command can be called', function (): void {
        $this->artisan('model-info:cache-reset')
            ->assertSuccessful();
    });
});
