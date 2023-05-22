<?php

namespace TeamNiftyGmbH\DataTable\Traits;

use Illuminate\Pagination\LengthAwarePaginator;

trait HasEloquentListeners
{
    public array $broadcastChannels = [];

    public function getPaginator(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        $this->broadcastChannels = [];
        foreach ($paginator->items() as $item) {
            $this->broadcastChannels[$item->getKey()] = $item->broadcastChannel();
        }

        if (
            in_array(BroadcastsEvents::class, class_uses_recursive($this->model))
            && $paginator->currentPage() === 1) {
            $this->broadcastChannels['created'] = $this->model::getBroadcastChannel(true);
        }

        return $paginator;
    }

    public function eloquentEventOccurred(string $event, array $data): void
    {
        $event = str_replace('.' . class_basename($this->model), 'echo', $event);

        $this->{$event}($data);
    }

    public function echoUpdated($eventData): void
    {
        $model = $this->getBuilder($this->model::query()->whereKey($eventData['model'][$this->modelKeyName]))->first();

        $item = $this->itemToArray($model);
        $data = \Arr::keyBy($this->data['data'], $this->modelKeyName);
        $data[$model->getKey()] = $item;
        $this->data['data'] = array_values($data);

        $this->skipRender();
    }

    public function echoCreated($data): void
    {
        $model = $this->getBuilder($this->model::query()->whereKey($data['model'][$this->modelKeyName]))->first();
        $item = $this->itemToArray($model);

        array_unshift($this->data['data'], $item);
        $this->data['total'] = $this->data['total'] + 1;
        $this->data['to'] = $this->data['to'] + 1;
        $this->data['from'] = $this->data['from'] ?: 1;

        if (count($this->data['data']) > $this->data['per_page']) {
            array_pop($this->data['data']);
        }

        $this->skipRender();
    }

    public function echoDeleted($eventData): void
    {
        $data = \Arr::keyBy($this->data['data'], $this->modelKeyName);
        unset($data[$eventData['model'][$this->modelKeyName]]);
        $this->data['data'] = array_values($data);
        unset($this->broadcastChannels[$eventData['model'][$this->modelKeyName]]);
        $this->data['total'] = $this->data['total'] - 1;
        $this->data['to'] = $this->data['to'] - 1;

        $this->skipRender();
    }
}
