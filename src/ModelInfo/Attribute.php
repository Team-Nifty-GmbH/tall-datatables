<?php

namespace TeamNiftyGmbH\DataTable\ModelInfo;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use TeamNiftyGmbH\DataTable\Contracts\HasFrontendFormatter;

class Attribute implements Arrayable
{
    public mixed $formatter = null;

    public function __construct(
        public string $name,
        public ?string $phpType,
        public ?string $type,
        public bool $increments,
        public ?bool $nullable,
        public mixed $default,
        public ?bool $primary,
        public ?bool $unique,
        public bool $fillable,
        public ?bool $appended,
        public ?string $cast,
        public bool $virtual,
        public bool $hidden,
    ) {}

    public function getFormatterType(Model|string $model): string|array
    {
        $modelInstance = is_string($model) ? new $model() : $model;

        if (in_array($this->cast, ['accessor', 'attribute']) && $modelInstance->hasCast($this->name)) {
            $this->cast = $modelInstance->getCasts()[$this->name];
        } elseif (
            in_array($this->cast, ['accessor', 'attribute'])
            && $this->phpType
            && class_exists($this->phpType)
        ) {
            $this->cast = $this->phpType;
        }

        if (
            class_exists($this->cast ?? false)
            && in_array(HasFrontendFormatter::class, class_implements($this->cast))
        ) {
            return $this->cast::getFrontendFormatter(modelClass: $model);
        }

        return strtolower(class_basename($this->cast ?? $this->phpType));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
