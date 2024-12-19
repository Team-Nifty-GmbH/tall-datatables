<?php

namespace TeamNiftyGmbH\DataTable\Traits;

use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

trait HasEloquentListeners
{
    public array $broadcastChannels = [];

    protected bool $withoutEloquentListeners = false;

    protected function getPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $model = $this->getModel();
        if ($this->withoutEloquentListeners ||
            ! in_array(BroadcastsEvents::class, class_uses_recursive($model))
        ) {
            return $paginator;
        }

        $this->broadcastChannels = [];
        foreach ($paginator->items() as $item) {
            $this->broadcastChannels[$item->getKey()] = $item->broadcastChannel();
        }

        if ($paginator->currentPage() === 1 && method_exists($model, 'getBroadcastChannel')) {
            $this->broadcastChannels['created'] = $model::getBroadcastChannel(true);
        } else {
            $newModel = new $model();
            $this->broadcastChannels['created'] = $newModel->broadcastChannel() . $model::max($newModel->getKeyName()) + 1;
        }

        return $paginator;
    }

    protected function getKeyFromEventData(array $eventData): int|string|null
    {
        return data_get($eventData, 'model.' . $this->modelKeyName);
    }

    public function eloquentEventOccurred(string $event, array $data): void
    {
        $event = str_replace('.' . class_basename($this->getModel()), 'echo', $event);

        $this->{$event}($data);
    }

    public function echoUpdated(array $eventData): void
    {
        $model = $this->buildSearch()->whereKey($this->getKeyFromEventData($eventData))->first();
        if (is_null($model)) {
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
        $model = $this->buildSearch()->whereKey($this->getKeyFromEventData($eventData))->first();
        if (is_null($model)) {
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
