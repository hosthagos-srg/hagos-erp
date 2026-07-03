<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekonsiliasiMp extends Model
{
    protected $table = 'rekonsiliasi_mps';
    protected $guarded = [];

    protected $casts = [
        'net_sistem' => 'float',
        'saldo_riil' => 'float',
        'selisih'    => 'float',
        'dibebankan' => 'boolean',
    ];
}
