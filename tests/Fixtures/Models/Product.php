<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TeamNiftyGmbH\DataTable\Casts\BcFloat;
use TeamNiftyGmbH\DataTable\Casts\Links\Image;
use TeamNiftyGmbH\DataTable\Casts\Links\Link;
use TeamNiftyGmbH\DataTable\Casts\Money;
use TeamNiftyGmbH\DataTable\Casts\Percentage;
use TeamNiftyGmbH\DataTable\Contracts\InteractsWithDataTables;

class Product extends Model implements InteractsWithDataTables
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'price' => Money::class,
            'discount' => Percentage::class,
            'quantity' => BcFloat::class,
            'website' => Link::class,
            'image_url' => Image::class,
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function getAvatarUrl(): ?string
    {
        return $this->image_url;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getLabel(): ?string
    {
        return $this->name;
    }

    public function getUrl(): ?string
    {
        return '/products/' . $this->getKey();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
