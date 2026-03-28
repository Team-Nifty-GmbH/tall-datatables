<?php

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\Model;
use TeamNiftyGmbH\DataTable\Traits\BroadcastsEvents;

describe('BroadcastsEvents', function (): void {
    test('broadcastOn returns PrivateChannel', function (): void {
        $model = new class() extends Model
        {
            use BroadcastsEvents;

            protected $table = 'users';
        };
        $model->id = 1;

        $channels = $model->broadcastOn('updated');
        expect($channels)->toBeArray()
            ->and($channels[0])->toBeInstanceOf(PrivateChannel::class);
    });

    test('broadcastAfterCommit returns true', function (): void {
        $model = new class() extends Model
        {
            use BroadcastsEvents;

            protected $table = 'users';
        };

        expect($model->broadcastAfterCommit())->toBeTrue();
    });

    test('broadcastWith returns array with model key', function (): void {
        $model = new class() extends Model
        {
            use BroadcastsEvents;

            protected $table = 'users';
        };
        $model->id = 42;

        $data = $model->broadcastWith();
        expect($data)->toBeArray()
            ->and($data)->toHaveKey('model')
            ->and($data['model'])->toBeArray();
    });
});
