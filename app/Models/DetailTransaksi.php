<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentTokoViaTransaksi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailTransaksi extends Model
{
    use BelongsToCurrentTokoViaTransaksi;

    protected $table = 'detail_transaksi';

    protected $fillable = [
        'jumlah',
        'harga_saat_transaksi',
        'subtotal',
        'transaksi_id',
        'produk_id',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'harga_saat_transaksi' => 'integer',
            'subtotal' => 'integer',
            'transaksi_id' => 'integer',
            'produk_id' => 'integer',
        ];
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }
}
