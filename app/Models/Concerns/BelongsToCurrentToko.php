<?php

namespace App\Models\Concerns;

use App\Models\Toko;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToCurrentToko
{
    public static function bootBelongsToCurrentToko(): void
    {
        static::addGlobalScope('current_toko', function (Builder $builder): void {
            $user = Auth::user();

            if ($user?->toko_id) {
                $builder->where($builder->qualifyColumn('toko_id'), $user->toko_id);
            }
        });

        static::creating(function ($model): void {
            $user = Auth::user();

            if ($user?->toko_id) {
                $model->toko_id = $user->toko_id;
            }
        });

        static::updating(function ($model): void {
            $user = Auth::user();

            if ($user?->toko_id) {
                $model->toko_id = $user->toko_id;
            }
        });
    }

    public function toko(): BelongsTo
    {
        return $this->belongsTo(Toko::class);
    }
}
