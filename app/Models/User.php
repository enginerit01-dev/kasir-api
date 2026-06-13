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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

#[Fillable(['name', 'email', 'username', 'password', 'is_active', 'role'])]
#[Hidden(['password'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasUlids;

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

    public function tokoMilik(): HasMany
    {
        return $this->hasMany(Toko::class,'owner_id');
    }

    public function tokoTugas(): BelongsToMany
    {
        return $this->belongsToMany(Toko::class, 'toko_user')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function hasActiveRole(array $roles): bool
    {
        if (in_array($this->role, $roles, true)) {
            return true;
        }

        $tokoAktif = $this->tokoTugas()->wherePivot('is_active', true)->first();
        $roleStaf = $tokoAktif?->pivot?->role;

        return $roleStaf && in_array($roleStaf, $roles, true);
    }

    //helper ambil toko id aktif tempat user bertugas
    // untuk staff (role=staff), ini ambil dari toko_user
    public function getTokoAktifId(): ?string
    {
        if ($this->role === 'owner') {
            return $this->tokoMilik()->first()?->id;
        }

        $pivot = $this->tokoTugas()
            ->wherePivot('is_active', true)
            ->first();
        return $pivot?->id;
    }

    //helper ambil role staf di toko tertentu
    public function getRoleAtToko(string $tokoId): ?string
    {
        $pivot = $this->tokoTugas()
        ->wherePivot('toko_id', $tokoId)
        ->wherePivot('is_active',true)
        ->first();
        return $pivot?->pivot->role;
    }


}
