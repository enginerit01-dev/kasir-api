<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentToko;
use Illuminate\Database\Eloquent\Model;

class PengaturanToko extends Model
{
    use BelongsToCurrentToko;

    protected $table = 'pengaturan_toko';

    protected $fillable = [
        'toko_id',
        'ppn',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'toko_id' => 'integer',
            'ppn' => 'integer',
        ];
    }
}
