<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToCurrentTokoViaTransaksi
{
    public static function bootBelongsToCurrentTokoViaTransaksi(): void
    {
        static::addGlobalScope('current_toko_via_transaksi', function (Builder $builder): void {
            $user = Auth::user();
            $tokoId = $user?->getTokoAktifId();

            if ($tokoId) {
                $builder->whereHas('transaksi', function (Builder $transaksiQuery) use ($tokoId): void {
                    $transaksiQuery->where('toko_id', $tokoId);
                });
            }
        });
    }
}
