<?php

namespace TeamNiftyGmbH\DataTable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

class DatatableUserSetting extends Model
{
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model): void {
            $model->authenticatable_id = $model->authenticatable_id ?? Auth::user()?->getKey();
            $model->authenticatable_type = $model->authenticatable_type ?? Auth::user()->getMorphClass();
        });
    }

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_layout' => 'boolean',
            'is_permanent' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }
}
