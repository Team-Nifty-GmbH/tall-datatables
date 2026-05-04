<?php

use Illuminate\Support\Facades\Storage;
use TeamNiftyGmbH\DataTable\Exports\JsonExport;
use Tests\Fixtures\Models\Post;

beforeEach(function (): void {
    $this->user = createTestUser(['name' => 'JSON User', 'email' => 'json@test.com']);
    $this->actingAs($this->user);
});

describe('JsonExport', function (): void {
    it('returns a streamed response', function (): void {
        createTestPost(['user_id' => $this->user->getKey()]);

        $export = new JsonExport(Post::query(), ['title']);
        $response = $export->download('test.json');

        expect($response)->toBeInstanceOf(Symfony\Component\HttpFoundation\StreamedResponse::class);
    });

    it('sets correct content type header', function (): void {
        $export = new JsonExport(Post::query(), ['title']);
        $response = $export->download('test.json');

        expect($response->headers->get('content-type'))->toContain('application/json');
    });

    it('sets content disposition header with filename', function (): void {
        $export = new JsonExport(Post::query(), ['title']);
        $response = $export->download('export.json');

        expect($response->headers->get('content-disposition'))->toContain('export.json');
    });

    it('outputs valid JSON array', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'JSON Post']);

        $export = new JsonExport(Post::query(), ['title']);
        $response = $export->download('test.json');

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $decoded = json_decode($output, true);
        expect($decoded)->toBeArray()
            ->and($decoded[0])->toHaveKey('title', 'JSON Post');
    });

    it('nests relation columns as objects', function (): void {
        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Nested']);

        $export = new JsonExport(Post::query()->with('user'), ['title', 'user.name', 'user.email']);
        $response = $export->download('test.json');

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $decoded = json_decode($output, true);
        expect($decoded[0]['user'])->toBeArray()
            ->and($decoded[0]['user']['name'])->toBe('JSON User')
            ->and($decoded[0]['user']['email'])->toBe('json@test.com');
    });

    it('outputs empty array for no data', function (): void {
        $export = new JsonExport(Post::query(), ['title']);
        $response = $export->download('test.json');

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        expect(json_decode($output, true))->toBe([]);
    });
});

describe('JsonExport store', function (): void {
    it('writes a json file to the given storage path', function (): void {
        Storage::fake('local');

        createTestPost([
            'user_id' => $this->user->getKey(),
            'title' => 'Stored JSON',
        ]);

        $export = new JsonExport(Post::query()->with('user'), ['title', 'user.name']);
        $result = $export->store('exports/test.json', 'local');

        expect($result)->toBeTrue();
        Storage::disk('local')->assertExists('exports/test.json');

        $contents = Storage::disk('local')->get('exports/test.json');
        $decoded = json_decode($contents, true);

        expect($decoded)->toBeArray()
            ->and($decoded[0])->toHaveKey('title', 'Stored JSON')
            ->and($decoded[0]['user'])->toBeArray()
            ->and($decoded[0]['user']['name'])->toBe('JSON User');
    });

    it('uses the default disk when none is passed', function (): void {
        Storage::fake();

        createTestPost(['user_id' => $this->user->getKey(), 'title' => 'Default Disk JSON']);

        $export = new JsonExport(Post::query(), ['title']);
        $export->store('exports/default.json');

        Storage::assertExists('exports/default.json');
    });
});
