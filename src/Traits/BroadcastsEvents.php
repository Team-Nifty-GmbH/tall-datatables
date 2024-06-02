<?php

namespace TeamNiftyGmbH\DataTable\Traits;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Eloquent\BroadcastableModelEventOccurred;
use Illuminate\Database\Eloquent\BroadcastsEvents as BaseBroadcastsEvents;
use ReflectionClass;

trait BroadcastsEvents
{
    use BaseBroadcastsEvents, InteractsWithSockets;

    protected static bool $includeRelations = false;

    public function broadcastChannel(bool $generic = false): string
    {
        $default = parent::broadcastChannel();

        if (! $generic) {
            return $default;
        }

        // Remove the id from the channel to get a non id specific channel.
        $broadcastChannelGeneric = explode('.', $default);
        array_pop($broadcastChannelGeneric);

        return implode('.', $broadcastChannelGeneric);
    }

    public static function getBroadcastChannel(bool $generic = true): string
    {
        $reflection = new ReflectionClass(static::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        return $instance->broadcastChannel($generic);
    }

    public static function getBroadcastChannelRoute(): string
    {
        $reflection = new ReflectionClass(static::class);
        $instance = $reflection->newInstanceWithoutConstructor();

        return $instance->broadcastChannelRoute();
    }

    protected function newBroadcastableEvent($event): BroadcastableModelEventOccurred
    {
        return (new BroadcastableModelEventOccurred($this, $event))->dontBroadcastToCurrentUser();
    }

    public function broadcastOn($event): array
    {
        return [new PrivateChannel($this->broadcastChannel($event === 'created'))];
    }

    public function broadcastWith(): array
    {
        // This ensures the payload doesnt get too large
        return ['model' => static::$includeRelations ? $this->toArray() : $this->withoutRelations()->toArray()];
    }

    public function broadcastAfterCommit(): bool
    {
        return true;
    }
}
