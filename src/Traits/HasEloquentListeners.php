<?php

namespace TeamNiftyGmbH\DataTable\Traits;

use Illuminate\Database\Eloquent\BroadcastsEvents;
use Illuminate\Support\Arr;
use Livewire\Attributes\Locked;

trait HasEloquentListeners
{
    public array $broadcastChannels = [];

    #[Locked]
    public array $broadcastRowKeys = [];

    protected bool $withoutEloquentListeners = false;

    public function echoCreated(array $eventData): void
    {
        if (empty($this->data)) {
            $this->loadData();

            return;
        }

        if ((int) data_get($this->data, 'current_page', 1) > 1) {
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
    }

    public function echoDeleted(array $eventData): void
    {
        if (empty($this->data)) {
            $this->loadData();

            return;
        }

        if (! $this->isLoadedRow($eventData)) {
            return;
        }

        $data = Arr::keyBy($this->data['data'], $this->modelKeyName);
        unset($data[$this->getKeyFromEventData($eventData)]);

        $this->data['data'] = array_values($data);
        $this->data['total'] = max(0, (int) ($this->data['total'] ?? 0) - 1);
        $this->data['to'] = max(0, (int) ($this->data['to'] ?? 0) - 1);
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

        if (! $this->isLoadedRow($eventData)) {
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

        if (method_exists($model, 'getBroadcastChannel')) {
            $this->broadcastChannels = ['model' => $model::getBroadcastChannel(true)];
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

    protected function isLoadedRow(array $eventData): bool
    {
        $key = $this->getKeyFromEventData($eventData);

        return ! is_null($key)
            && in_array((string) $key, array_map('strval', $this->rowKeys($this->data)), true);
    }

    protected function rowKeys(array $data): array
    {
        return array_column(
            array_merge(
                data_get($data, 'data') ?? [],
                ...array_map(
                    fn (mixed $group): array => data_get($group, 'data') ?? [],
                    data_get($data, 'groups') ?? []
                )
            ),
            $this->modelKeyName
        );
    }

    protected function setData(array $data): void
    {
        parent::setData($data);

        $this->broadcastRowKeys = $this->rowKeys($data);
    }
}
