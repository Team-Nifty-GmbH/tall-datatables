<?php

namespace TeamNiftyGmbH\DataTable\Traits;

use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

trait HasEloquentListeners
{
    public array $broadcastChannels = [];

    protected bool $withoutEloquentListeners = false;

    public function echoCreated(array $eventData): void
    {
        if (empty($this->data)) {
            $this->loadData();

            return;
        }

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
    }

    public function echoDeleted(array $eventData): void
    {
        if (empty($this->data)) {
            $this->loadData();

            return;
        }

        $data = Arr::keyBy($this->data['data'], $this->modelKeyName);
        unset(
            $data[$eventData['model'][$this->modelKeyName]],
            $this->broadcastChannels[$eventData['model'][$this->modelKeyName]]
        );

        $this->data['data'] = array_values($data);
        $this->data['total']--;
        $this->data['to']--;
    }

    public function echoRestored(array $eventData): void
    {
        $this->echoCreated($eventData);
    }

    public function echoTrashed(array $eventData): void
    {
        $this->echoDeleted($eventData);
    }

    public function echoUpdated(array $eventData): void
    {
        if (empty($this->data)) {
            $this->loadData();

            return;
        }

        $model = $this->buildSearch()->whereKey($this->getKeyFromEventData($eventData))->first();
        if (is_null($model)) {
            $this->echoDeleted($eventData);

            return;
        }

        $item = $this->itemToArray($model);
        $data = Arr::keyBy($this->data['data'], $this->modelKeyName);
        $data[$model->getKey()] = $item;
        $this->data['data'] = array_values($data);
    }

    public function eloquentEventOccurred(string $event, array $data): void
    {
        $event = str_replace('.' . class_basename($this->getModel()), 'echo', $event);

        if (! method_exists($this, $event)) {
            return;
        }

        $this->{$event}($data);
    }

    /**
     * Return Echo event-to-method mapping for Livewire listeners.
     *
     * @return array<string, string>
     */
    public function getEloquentListeners(): array
    {
        $model = $this->getModel();

        if ($this->withoutEloquentListeners ||
            ! in_array(BroadcastsEvents::class, class_uses_recursive($model))
        ) {
            return [];
        }

        $baseName = class_basename($model);

        return [
            ".{$baseName}Created" => 'echoCreated',
            ".{$baseName}Updated" => 'echoUpdated',
            ".{$baseName}Deleted" => 'echoDeleted',
            ".{$baseName}Trashed" => 'echoTrashed',
            ".{$baseName}Restored" => 'echoRestored',
        ];
    }

    public function mountHasEloquentListeners(): void
    {
        $model = $this->getModel();

        if ($this->withoutEloquentListeners ||
            ! in_array(BroadcastsEvents::class, class_uses_recursive($model))
        ) {
            return;
        }

        // Pre-populate the created channel so Echo listeners
        // can subscribe immediately after mount.
        if (method_exists($model, 'getBroadcastChannel')) {
            $this->broadcastChannels['created'] = $model::getBroadcastChannel(true);
        } else {
            $newModel = new $model();
            $this->broadcastChannels['created'] = $newModel->broadcastChannel() . '1';
        }
    }

    /**
     * Trigger a full reload of the data.
     */
    public function refreshData(): void
    {
        $this->loadData();
    }

    /**
     * Re-query a single row from the database and replace it in $this->data.
     */
    public function refreshRow(array $eventData): void
    {
        if (empty($this->data)) {
            $this->loadData();

            return;
        }

        $key = $this->getKeyFromEventData($eventData);
        if (is_null($key)) {
            return;
        }

        $model = $this->buildSearch()->whereKey($key)->first();
        if (is_null($model)) {
            $this->echoDeleted($eventData);

            return;
        }

        $item = $this->itemToArray($model);
        $data = Arr::keyBy($this->data['data'], $this->modelKeyName);
        $data[$model->getKey()] = $item;
        $this->data['data'] = array_values($data);
    }

    protected function getKeyFromEventData(array $eventData): int|string|null
    {
        return data_get($eventData, 'model.' . $this->modelKeyName);
    }

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
            $this->broadcastChannels['created'] = $newModel->broadcastChannel() . ($model::max($newModel->getKeyName()) + 1);
        }

        return $paginator;
    }
}
