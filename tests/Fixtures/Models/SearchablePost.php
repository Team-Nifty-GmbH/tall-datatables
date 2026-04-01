<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use TeamNiftyGmbH\DataTable\Contracts\InteractsWithDataTables;

class SearchablePost extends Model implements InteractsWithDataTables
{
    use Searchable, SoftDeletes;

    protected $table = 'posts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->getKey(),
            'title' => $this->title,
            'content' => $this->content,
        ];
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id');
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
