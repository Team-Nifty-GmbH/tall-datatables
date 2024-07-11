<?php

namespace TeamNiftyGmbH\DataTable\Traits;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

trait HasEloquentListeners
{
    public array $broadcastChannels = [];

    protected function getPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $this->broadcastChannels = [];
        foreach ($paginator->items() as $item) {
            $this->broadcastChannels[$item->getKey()] = $item->broadcastChannel();
        }

        if (
            in_array(BroadcastsEvents::class, class_uses_recursive($this->getModel()))
            && $paginator->currentPage() === 1
        ) {
            $this->broadcastChannels['created'] = $this->getModel()::getBroadcastChannel(true);
        }

        return $paginator;
    }

    public function eloquentEventOccurred(string $event, array $data): void
    {
        $event = str_replace('.' . class_basename($this->getModel()), 'echo', $event);

        $this->{$event}($data);
    }

    public function echoUpdated(array $eventData): void
    {
        $model = $this->buildSearch()->whereKey($eventData['model'][$this->modelKeyName])->first();
        if ($model === null) {
            // seems like the model doesnt match the search criteria
            $this->echoDeleted($eventData);

            return;
        }

        $item = $this->itemToArray($model);
        $data = Arr::keyBy($this->data['data'], $this->modelKeyName);
        $data[$model->getKey()] = $item;
        $this->data['data'] = array_values($data);

        $this->skipRender();
    }

    public function echoCreated(array $eventData): void
    {
        $model = $this->buildSearch()->whereKey($eventData['model'][$this->modelKeyName])->first();
        if ($model === null) {
            return;
        }

        $item = $this->itemToArray($model);

        array_unshift($this->data['data'], $item);
        $this->data['total']++;
        $this->data['to']++;
        $this->data['from'] = $this->data['from'] ?: 1;

        if (count($this->data['data']) > $this->data['per_page']) {
            array_pop($this->data['data']);
        }

        $this->broadcastChannels[$model->getKey()] = $model->broadcastChannel();

        $this->skipRender();
    }

    public function echoDeleted(array $eventData): void
    {
        $data = Arr::keyBy($this->data['data'], $this->modelKeyName);
        unset(
            $data[$eventData['model'][$this->modelKeyName]],
            $this->broadcastChannels[$eventData['model'][$this->modelKeyName]]
        );

        $this->data['data'] = array_values($data);
        $this->data['total']--;
        $this->data['to']--;

        $this->skipRender();
    }

    public function echoTrashed(array $eventData): void
    {
        $this->echoDeleted($eventData);
    }

    public function echoRestored(array $eventData): void
    {
        $this->echoCreated($eventData);
    }
}
