<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\BelongsToCurrentToko;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'username', 'password', 'is_active', 'role', 'toko_id'])]
#[Hidden(['password'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use BelongsToCurrentToko, HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function toko(): BelongsTo
    {
        return $this->belongsTo(Toko::class);
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }
}
