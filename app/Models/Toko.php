<?php

namespace App\Models;

use Database\Factories\TokoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Toko extends Model
{
    /** @use HasFactory<TokoFactory> */
    use HasFactory;

    protected $table = 'toko';

    protected $fillable = [
        'nama',
        'alamat',
        'telepon',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function produk(): HasMany
    {
        return $this->hasMany(Produk::class);
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }

    public function pengaturan(): HasMany
    {
        return $this->hasMany(PengaturanToko::class);
    }
}
