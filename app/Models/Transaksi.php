<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentToko;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaksi extends Model
{
    use BelongsToCurrentToko;

    protected $table = 'transaksi';

    protected $fillable = [
        'kode_transaksi',
        'tanggal',
        'subtotal',
        'total_ppn',
        'grand_total',
        'nominal_bayar',
        'metode_pembayaran',
        'kembalian',
        'status',
        'user_id',
        'toko_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'datetime',
            'subtotal' => 'integer',
            'total_ppn' => 'integer',
            'grand_total' => 'integer',
            'nominal_bayar' => 'integer',
            'kembalian' => 'integer',
            'status' => 'integer',
            'user_id' => 'integer',
            'toko_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function detailTransaksi(): HasMany
    {
        return $this->hasMany(DetailTransaksi::class, 'transaksi_id');
    }
}
