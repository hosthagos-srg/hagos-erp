<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnggaranMingguan extends Model
{
    use HasFactory;

    protected $table = 'anggaran_mingguan';
    protected $guarded = [];

    protected $casts = [
        'jumlah_mingguan' => 'decimal:2',
        'aktif'           => 'boolean',
    ];
}
