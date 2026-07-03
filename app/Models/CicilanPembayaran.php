<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CicilanPembayaran extends Model
{
    protected $table = 'cicilan_pembayaran';
    protected $guarded = [];
    protected $casts = [
        'periode' => 'date',
        'tgl_bayar' => 'date',
    ];

    public function utangCicilan()
    {
        return $this->belongsTo(UtangCicilan::class);
    }
}
