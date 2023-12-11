<?php

namespace TeamNiftyGmbH\DataTable\Helpers;

use Illuminate\Support\Str;

class AggregatableRelationColumn
{
    public static function make(
        string|array $relation,
        string $column,
        string $function = 'sum',
        ?string $alias = null
    ): static {
        return new static($relation, $column, $function, $alias);
    }

    public function __construct(
        public string|array $relation,
        public string $column,
        public string $function = 'sum',
        public ?string $alias = null
    ) {
        $this->alias = $this->alias ?? Str::snake(is_string($this->relation) ? $this->relation : array_key_first($this->relation)) . '_' . $function . '_' . $column;

        if (is_array($this->relation)) {
            $keyName = array_key_first($this->relation) . ' as ' . $this->alias;
            $relation = array_pop($this->relation);
            $this->relation[$keyName] = $relation;
        }
    }
}
