<?php

namespace TeamNiftyGmbH\DataTable\Helpers;

use Closure;
use Laravel\SerializableClosure\SerializableClosure;
use Serializable;
use TeamNiftyGmbH\DataTable\DataTable;

class SessionFilter implements Serializable
{
    public function __construct(
        public string|DataTable|null $dataTableCacheKey = null,
        public Closure|SerializableClosure|null $closure = null,
        public ?string $name = null,
        public bool $loaded = false
    ) {
        $this->setDataTableCacheKey($this->dataTableCacheKey);
        $this->setClosure($this->closure);
    }

    public static function make(string|DataTable $dataTableCacheKey, Closure $closure, string $name): static
    {
        return new static($dataTableCacheKey, $closure, $name);
    }

    public function setClosure(callable $closure): static
    {
        $this->closure = $closure instanceof SerializableClosure
            ? $closure
            : new SerializableClosure($closure);

        return $this;
    }

    public function getClosure(): Closure
    {
        return $this->closure->getClosure();
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function setDataTableCacheKey(string|DataTable $dataTableCacheKey): static
    {
        $this->dataTableCacheKey = $dataTableCacheKey instanceof DataTable
            ? $dataTableCacheKey->getCacheKey()
            : $dataTableCacheKey;

        return $this;
    }

    public function store(): void
    {
        session()
            ->put(
                $this->dataTableCacheKey . '_query',
                $this
            );
    }

    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    public function unserialize(string $data): void
    {
        $this->__unserialize(unserialize($data));
    }

    public function __serialize(): array
    {
        return [
            'dataTableCacheKey' => data_get($this, 'dataTableCacheKey'),
            'closure' => data_get($this, 'closure'),
            'name' => data_get($this, 'name'),
            'loaded' => data_get($this, 'loaded'),
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->dataTableCacheKey = data_get($data, 'dataTableCacheKey');
        $this->closure = data_get($data, 'closure');
        $this->name = data_get($data, 'name');
        $this->loaded = data_get($data, 'loaded', false);
    }
}
