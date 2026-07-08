<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gaji extends Model
{
    protected $table = 'gaji';
    protected $guarded = [];
    protected $casts = [
        'tanggal_bayar'   => 'date',
        'bulan_biaya'     => 'date',
        'gaji_pokok'      => 'float',
        'tunjangan'       => 'float',
        'potongan_kasbon' => 'float',
        'potongan_lain'   => 'float',
        'gaji_bersih'     => 'float',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }
}
