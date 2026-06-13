<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentToko;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

class PengaturanToko extends Model
{
    use BelongsToCurrentToko, HasUlids;

    protected $table = 'pengaturan_toko';

    protected $fillable = [
        'toko_id',
        'ppn',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'toko_id' => 'string',
            'ppn' => 'integer',
        ];
    }
}
