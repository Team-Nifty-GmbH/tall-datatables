<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use TeamNiftyGmbH\DataTable\Contracts\InteractsWithDataTables;
use TeamNiftyGmbH\DataTable\Traits\BroadcastsEvents;

class BroadcastablePost extends Model implements InteractsWithDataTables
{
    use BroadcastsEvents, SoftDeletes;

    protected $table = 'posts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function getAvatarUrl(): ?string
    {
        return null;
    }

    public function getDescription(): ?string
    {
        return substr($this->content, 0, 100);
    }

    public function getLabel(): ?string
    {
        return $this->title;
    }

    public function getUrl(): ?string
    {
        return '/posts/' . $this->getKey();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
