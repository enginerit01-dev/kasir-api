<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentTokoViaTransaksi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

class DetailTransaksi extends Model
{
    use BelongsToCurrentTokoViaTransaksi, HasUlids;

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
            'transaksi_id' => 'string',
            'produk_id' => 'string',
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
