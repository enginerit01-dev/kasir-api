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

            if (! $user) return;

            if ($user->role === 'super_admin') return;

            //filter toko milik owner

            if ($user->role === 'owner') {
                $tokoIds = $user->tokoMilik()->pluck('id');
                $builder->whereIn($builder->qualifyColumn('toko_id'),$tokoIds);
                return;
            }

            //staff (admin/kasir): filter ke toko tempat mereka bertugas
            $toko = $user->tokoTugas()->wherePivot('is_active', true)->first();
            if ($toko){
                $builder->where($builder->qualifyColumn('toko_id'), $toko->id);
            }

        });

        static::creating(function ($model): void {
            $user = Auth::user();

            if(! $user) return;

            if($user->role === 'super_admin') return;

            //owner: jika tidak ada toko di model ambil toko pertama miliknya
            if ($user->role === 'owner'){
                if (! $model->toko_id) {
                    $model->toko_id = $user->getTokoAktifId();
                }
                return;
            }

            //staff set toko_id otomatis dari toko tempat bertugas
            $tokoId = $user->getTokoAktifId();
            if ($tokoId){
                $model->toko_id = $tokoId;
            }
        });

        static::updating(function ($model): void {
            $user = Auth::user();
            if ($user) {
                $tokoId = $user->getTokoAktifId();
                if ($tokoId) {
                    $model->toko_id = $tokoId;
                }
            }
        });
    }

    public function toko(): BelongsTo
    {
        return $this->belongsTo(Toko::class);
    }
}
