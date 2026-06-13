<?php

namespace App\Models;

use Database\Factories\TokoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Toko extends Model
{
    /** @use HasFactory<TokoFactory> */
    use HasFactory, HasUlids;

    protected $table = 'toko';

    protected $fillable = [
        'nama',
        'alamat',
        'telepon',
        'is_active',
        'owner_id'
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function staf(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'toko_user')
        ->withPivot('role', 'is_active')
        ->withTimestamps();
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
