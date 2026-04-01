<?php

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);

    $this->assetPath = dirname(__DIR__, 2) . '/dist/build/assets/';
});

describe('AssetController scripts', function (): void {
    test('serves javascript file with matching id parameter', function (): void {
        $jsFiles = File::glob($this->assetPath . 'tall-datatables*.js');

        if (empty($jsFiles)) {
            $this->markTestSkipped('No JS asset files found');
        }

        $fileName = basename($jsFiles[0]);
        $id = str_replace('tall-datatables-', '', $fileName);

        $response = $this->get(route('tall-datatables.assets.scripts', ['id' => $id]));

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('text/javascript');
    });

    test('falls back to glob when id file does not exist', function (): void {
        $response = $this->get(route('tall-datatables.assets.scripts', ['id' => 'nonexistent.js']));

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('text/javascript');
    });

    test('without id parameter falls back to directory path causing error', function (): void {
        // When no id is provided, $path is null, and file_exists($assetPath . null)
        // checks if the directory exists (which it does), so $path becomes the directory.
        // This causes Utils::pretendResponseIsFile to fail with a FileNotFoundException.
        $response = $this->get(route('tall-datatables.assets.scripts'));

        $response->assertServerError();
    });
});

describe('AssetController styles', function (): void {
    test('serves CSS file with matching id parameter', function (): void {
        $cssFiles = File::glob($this->assetPath . 'tall-datatables*.css');

        if (empty($cssFiles)) {
            $this->markTestSkipped('No CSS asset files found');
        }

        $fileName = basename($cssFiles[0]);
        $id = str_replace('tall-datatables-', '', $fileName);

        $response = $this->get(route('tall-datatables.assets.styles', ['id' => $id]));

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('text/css');
    });

    test('falls back to glob when id file does not exist for styles', function (): void {
        $response = $this->get(route('tall-datatables.assets.styles', ['id' => 'nonexistent.css']));

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('text/css');
    });

    test('without id parameter falls back to directory path causing error', function (): void {
        $response = $this->get(route('tall-datatables.assets.styles'));

        $response->assertServerError();
    });
});

describe('AssetController response headers', function (): void {
    test('script response has correct content type', function (): void {
        $response = $this->get(route('tall-datatables.assets.scripts', ['id' => 'nonexistent.js']));

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('text/javascript');
    });

    test('style response has correct content type', function (): void {
        $response = $this->get(route('tall-datatables.assets.styles', ['id' => 'nonexistent.css']));

        $response->assertOk();
        expect($response->headers->get('Content-Type'))->toContain('text/css');
    });

    test('script response includes cache headers', function (): void {
        $response = $this->get(route('tall-datatables.assets.scripts', ['id' => 'nonexistent.js']));

        $response->assertOk();
        expect($response->headers->get('Cache-Control'))->not->toBeNull();
    });

    test('style response includes cache headers', function (): void {
        $response = $this->get(route('tall-datatables.assets.styles', ['id' => 'nonexistent.css']));

        $response->assertOk();
        expect($response->headers->get('Cache-Control'))->not->toBeNull();
    });
});

describe('AssetController glob fallback', function (): void {
    test('scripts glob finds js file matching tall-datatables pattern', function (): void {
        $jsFiles = File::glob($this->assetPath . 'tall-datatables*.js');

        expect($jsFiles)->not->toBeEmpty();
        expect(basename($jsFiles[0]))->toStartWith('tall-datatables');
        expect(basename($jsFiles[0]))->toEndWith('.js');
    });

    test('styles glob finds css file matching tall-datatables pattern', function (): void {
        $cssFiles = File::glob($this->assetPath . 'tall-datatables*.css');

        expect($cssFiles)->not->toBeEmpty();
        expect(basename($cssFiles[0]))->toStartWith('tall-datatables');
        expect(basename($cssFiles[0]))->toEndWith('.css');
    });
});

describe('AssetController file content', function (): void {
    test('script response serves an actual file', function (): void {
        $jsFiles = File::glob($this->assetPath . 'tall-datatables*.js');

        if (empty($jsFiles)) {
            $this->markTestSkipped('No JS asset files found');
        }

        $fileName = basename($jsFiles[0]);
        $id = str_replace('tall-datatables-', '', $fileName);

        $response = $this->get(route('tall-datatables.assets.scripts', ['id' => $id]));

        $response->assertOk();
        // BinaryFileResponse serves files directly, getContent() returns false for these
        $baseResponse = $response->baseResponse;
        expect($baseResponse)->toBeInstanceOf(Symfony\Component\HttpFoundation\BinaryFileResponse::class);
        expect($baseResponse->getFile()->getPathname())->toBe($jsFiles[0]);
    });

    test('style response serves an actual file', function (): void {
        $cssFiles = File::glob($this->assetPath . 'tall-datatables*.css');

        if (empty($cssFiles)) {
            $this->markTestSkipped('No CSS asset files found');
        }

        $fileName = basename($cssFiles[0]);
        $id = str_replace('tall-datatables-', '', $fileName);

        $response = $this->get(route('tall-datatables.assets.styles', ['id' => $id]));

        $response->assertOk();
        $baseResponse = $response->baseResponse;
        expect($baseResponse)->toBeInstanceOf(Symfony\Component\HttpFoundation\BinaryFileResponse::class);
        expect($baseResponse->getFile()->getPathname())->toBe($cssFiles[0]);
    });
});
