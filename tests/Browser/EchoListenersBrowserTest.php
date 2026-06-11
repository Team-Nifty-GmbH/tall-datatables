<?php

/**
 * Browser tests for the Echo listener setup in data-table.js.
 *
 * Components without the HasEloquentListeners trait have no public
 * broadcastChannels property. Accessing it through $wire makes Livewire
 * treat the access as a method call, which fails server-side with a
 * MethodNotFoundException. The echo setup must therefore check that the
 * property exists before touching it.
 */

use Tests\Fixtures\Livewire\EchoBroadcastablePostDataTable;
use Tests\Fixtures\Livewire\EchoPlainPostDataTable;
use Tests\Fixtures\Models\BroadcastablePost;

beforeEach(function (): void {
    $manifestPath = dirname(__DIR__, 2) . '/dist/build/manifest.json';
    if (! file_exists($manifestPath)) {
        $this->markTestSkipped('Browser tests require built assets. Run: npm run build');
    }

    createTestUser(['name' => 'Test User', 'email' => 'test@example.com']);
});

describe('Echo Listeners', function (): void {
    it('does not probe broadcastChannels on components without the trait', function (): void {
        createTestPost(['title' => 'Plain Echo Post']);

        $page = visitLivewire(EchoPlainPostDataTable::class);

        $page->assertSee('Plain Echo Post')
            ->wait(1)
            ->assertNoJavascriptErrors();

        $result = $page->script('() => ({
            broadcastCalls: window.__lwBroadcastCalls.length,
            channels: window.__echoChannels.length,
        })');

        expect($result['broadcastCalls'])->toBe(0)
            ->and($result['channels'])->toBe(0);
    });

    it('subscribes to broadcast channels when the trait is present', function (): void {
        $post = BroadcastablePost::create([
            'user_id' => createTestUser()->getKey(),
            'title' => 'Broadcastable Echo Post',
            'content' => 'Broadcastable content',
            'is_published' => true,
        ]);

        $page = visitLivewire(EchoBroadcastablePostDataTable::class);

        $page->assertSee('Broadcastable Echo Post')
            ->wait(1)
            ->assertNoJavascriptErrors();

        $channels = $page->script('() => window.__echoChannels');

        expect($channels)->toContain($post->broadcastChannel());
    });
});
