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

    protected $casts = [
        'settings' => 'array',
        'is_layout' => 'boolean',
        'is_permanent' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->authenticatable_id = $model->authenticatable_id ?? Auth::user()->id;
            $model->authenticatable_type = $model->authenticatable_type ?? Auth::user()->getMorphClass();
        });
    }

    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }
}
