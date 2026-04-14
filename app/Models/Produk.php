<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentToko;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produk extends Model
{
    use HasFactory, BelongsToCurrentToko;

    protected $table = 'produk';

    protected $fillable = [
        'nama',
        'harga',
        'stok',
        'kategori_id',
        'kode_produk',
        'is_active',
        'toko_id',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'integer',
            'stok' => 'integer',
            'is_active' => 'boolean',
            'toko_id' => 'integer',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriProduk::class, 'kategori_id');
    }

    public function detailTransaksi(): HasMany
    {
        return $this->hasMany(DetailTransaksi::class, 'produk_id');
    }
}
