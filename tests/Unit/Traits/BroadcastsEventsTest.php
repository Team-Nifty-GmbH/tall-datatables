<?php

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\BroadcastableModelEventOccurred;
use Illuminate\Database\Eloquent\Model;
use TeamNiftyGmbH\DataTable\Traits\BroadcastsEvents;
use Tests\Fixtures\Models\BroadcastablePost;

describe('BroadcastsEvents – broadcastOn', function (): void {
    test('broadcastOn returns PrivateChannel for non-created events', function (): void {
        $model = new BroadcastablePost();
        $model->id = 1;

        $channels = $model->broadcastOn('updated');
        expect($channels)->toBeArray()
            ->and($channels)->toHaveCount(1)
            ->and($channels[0])->toBeInstanceOf(PrivateChannel::class);
    });

    test('broadcastOn returns generic channel for created event', function (): void {
        $model = new BroadcastablePost();
        $model->id = 1;

        $channels = $model->broadcastOn('created');
        expect($channels)->toBeArray()
            ->and($channels)->toHaveCount(1)
            ->and($channels[0])->toBeInstanceOf(PrivateChannel::class);
    });

    test('broadcastOn created channel does not include model id', function (): void {
        $model = new BroadcastablePost();
        $model->id = 42;

        $channels = $model->broadcastOn('created');
        $channelName = $channels[0]->name;

        // For created events, broadcastChannel(true) removes the ID
        expect($channelName)->not->toContain('.42');
    });

    test('broadcastOn non-created channel includes model id', function (): void {
        $model = new BroadcastablePost();
        $model->id = 42;

        $channels = $model->broadcastOn('updated');
        $channelName = $channels[0]->name;

        expect($channelName)->toContain('.42');
    });
});

describe('BroadcastsEvents – broadcastAfterCommit', function (): void {
    test('broadcastAfterCommit returns true', function (): void {
        $model = new BroadcastablePost();

        expect($model->broadcastAfterCommit())->toBeTrue();
    });
});

describe('BroadcastsEvents – broadcastWith', function (): void {
    test('broadcastWith returns array with model key', function (): void {
        $model = new BroadcastablePost();
        $model->id = 42;
        $model->title = 'Test Title';

        $data = $model->broadcastWith();
        expect($data)->toBeArray()
            ->and($data)->toHaveKey('model')
            ->and($data['model'])->toBeArray();
    });

    test('broadcastWith excludes relations by default', function (): void {
        $model = new BroadcastablePost();
        $model->id = 1;
        $model->title = 'Test';
        // Simulate a loaded relation
        $model->setRelation('user', new Tests\Fixtures\Models\User(['name' => 'Test User']));

        $data = $model->broadcastWith();
        // By default, includeRelations is false, so relations should be excluded
        expect($data['model'])->not->toHaveKey('user');
    });

    test('broadcastWith includes relations when includeRelations is true', function (): void {
        $model = new BroadcastablePost();
        $model->id = 1;
        $model->title = 'Test';
        $model->setRelation('user', new Tests\Fixtures\Models\User(['name' => 'Test User']));

        // Enable includeRelations via reflection
        $reflection = new ReflectionProperty(BroadcastablePost::class, 'includeRelations');
        $reflection->setValue(null, true);

        $data = $model->broadcastWith();
        expect($data['model'])->toHaveKey('user');

        // Reset
        $reflection->setValue(null, false);
    });

    test('broadcastWith includes model attributes', function (): void {
        $model = new BroadcastablePost();
        $model->id = 1;
        $model->title = 'My Title';
        $model->content = 'My Content';

        $data = $model->broadcastWith();
        expect($data['model'])->toHaveKey('id', 1)
            ->and($data['model'])->toHaveKey('title', 'My Title')
            ->and($data['model'])->toHaveKey('content', 'My Content');
    });

    test('broadcastWith respects broadcastWithout', function (): void {
        $model = new class() extends Model
        {
            use BroadcastsEvents;

            protected $guarded = ['id'];

            protected $table = 'posts';

            protected function broadcastWithout(): array
            {
                return ['content', 'secret_field'];
            }
        };
        $model->id = 1;
        $model->title = 'Test';
        $model->content = 'Should be excluded';

        $data = $model->broadcastWith();
        expect($data['model'])->not->toHaveKey('content')
            ->and($data['model'])->toHaveKey('title');
    });
});

describe('BroadcastsEvents – broadcastChannel', function (): void {
    test('broadcastChannel returns channel with model id by default', function (): void {
        $model = new BroadcastablePost();
        $model->id = 99;

        $channel = $model->broadcastChannel();
        expect($channel)->toContain('.99')
            ->and($channel)->toBeString();
    });

    test('broadcastChannel with generic=true removes the id', function (): void {
        $model = new BroadcastablePost();
        $model->id = 99;

        $genericChannel = $model->broadcastChannel(true);
        $specificChannel = $model->broadcastChannel(false);

        expect($genericChannel)->not->toContain('.99')
            ->and($specificChannel)->toContain('.99');
    });

    test('broadcastChannel generic is prefix of specific channel', function (): void {
        $model = new BroadcastablePost();
        $model->id = 5;

        $genericChannel = $model->broadcastChannel(true);
        $specificChannel = $model->broadcastChannel(false);

        expect($specificChannel)->toStartWith($genericChannel);
    });
});

describe('BroadcastsEvents – getBroadcastChannel static', function (): void {
    test('getBroadcastChannel returns generic channel by default', function (): void {
        $channel = BroadcastablePost::getBroadcastChannel(true);

        // Generic channel should not contain a numeric ID at the end
        expect($channel)->toBeString()
            ->and($channel)->not->toMatch('/\.\d+$/');
    });

    test('getBroadcastChannel with generic=false returns specific channel', function (): void {
        $channel = BroadcastablePost::getBroadcastChannel(false);

        // Without constructor (newInstanceWithoutConstructor), id will be null
        // so the channel should end with a dot and nothing (or null)
        expect($channel)->toBeString();
    });
});

describe('BroadcastsEvents – getBroadcastChannelRoute static', function (): void {
    test('getBroadcastChannelRoute returns a channel route string', function (): void {
        $route = BroadcastablePost::getBroadcastChannelRoute();

        expect($route)->toBeString()
            ->and($route)->toContain('BroadcastablePost');
    });

    test('getBroadcastChannelRoute contains parameter placeholder', function (): void {
        $route = BroadcastablePost::getBroadcastChannelRoute();

        // The route pattern should contain a {camelCaseName} placeholder
        expect($route)->toMatch('/\{[a-zA-Z]+\}/');
    });
});

describe('BroadcastsEvents – newBroadcastableEvent', function (): void {
    test('newBroadcastableEvent creates a BroadcastableModelEventOccurred', function (): void {
        $model = new BroadcastablePost();
        $model->id = 1;

        // Use reflection to call the protected method
        $reflection = new ReflectionMethod($model, 'newBroadcastableEvent');
        $event = $reflection->invoke($model, 'created');

        expect($event)->toBeInstanceOf(BroadcastableModelEventOccurred::class);
    });
});
