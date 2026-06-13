<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentToko;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Produk extends Model
{
    use HasFactory, BelongsToCurrentToko, HasUlids;

    protected $table = 'produk';

    protected $fillable = [
        'nama',
        'gambar',
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
            'toko_id' => 'string',
            'gambar' => 'string',
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
