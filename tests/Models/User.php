<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Scout\Searchable;
use TeamNiftyGmbH\DataTable\Contracts\InteractsWithDataTables;
use TeamNiftyGmbH\DataTable\Traits\BroadcastsEvents;
use TeamNiftyGmbH\DataTable\Traits\HasDatatableUserSettings;
use TeamNiftyGmbH\DataTable\Traits\HasFrontendAttributes;
use Tests\Database\Factories\UserFactory;

class User extends Authenticatable implements InteractsWithDataTables
{
    use BroadcastsEvents, HasDatatableUserSettings, HasFactory, HasFrontendAttributes, Searchable;

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected string $detailRouteName = 'users.show';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected string $iconName = 'user';

    public static function newFactory(): UserFactory
    {
        return UserFactory::new();
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
        return route('users.show', $this->id);
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
