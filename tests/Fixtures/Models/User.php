<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use TeamNiftyGmbH\DataTable\Contracts\InteractsWithDataTables;
use TeamNiftyGmbH\DataTable\Traits\HasDatatableUserSettings;

class User extends Authenticatable implements InteractsWithDataTables
{
    use HasDatatableUserSettings, SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = ['password'];

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function getAvatarUrl(): ?string
    {
        return null;
    }

    public function getDescription(): ?string
    {
        return $this->email;
    }

    public function getLabel(): ?string
    {
        return $this->name;
    }

    public function getUrl(): ?string
    {
        return '/users/' . $this->getKey();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
