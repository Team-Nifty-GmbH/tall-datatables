<?php

namespace TeamNiftyGmbH\DataTable\ModelInfo;

use Illuminate\Database\Eloquent\Model;
use Spatie\ModelInfo\Attributes\Attribute as BaseAttribute;
use TeamNiftyGmbH\DataTable\Contracts\HasFrontendFormatter;

class Attribute extends BaseAttribute
{
    public mixed $formatter;

    public static function fromBase(BaseAttribute $baseAttribute): self
    {
        return new self(
            name: $baseAttribute->name,
            phpType: $baseAttribute->phpType,
            type: $baseAttribute->type,
            increments: $baseAttribute->increments,
            nullable: $baseAttribute->nullable,
            default: $baseAttribute->default,
            primary: $baseAttribute->primary,
            unique: $baseAttribute->unique,
            fillable: $baseAttribute->fillable,
            appended: $baseAttribute->appended,
            cast: $baseAttribute->cast,
            virtual: $baseAttribute->virtual,
            hidden: $baseAttribute->hidden,
        );
    }

    public function getFormatterType (Model|string $model): string|array
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
}
