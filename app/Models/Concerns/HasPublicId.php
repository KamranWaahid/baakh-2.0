<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait HasPublicId
{
    protected static function bootHasPublicId(): void
    {
        static::creating(function ($model) {
            if (!empty($model->public_id) || !Schema::hasColumn($model->getTable(), 'public_id')) {
                return;
            }

            $prefix = property_exists($model, 'publicIdPrefix') ? $model->publicIdPrefix : 'pub';
            $model->public_id = $prefix . '_' . Str::lower((string) Str::ulid());
        });
    }
}
