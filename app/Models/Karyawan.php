<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    protected $table = 'karyawan';
    protected $guarded = [];
    protected $casts = [
        'gaji_pokok' => 'float',
        'tgl_masuk'  => 'date',
    ];

    public function kasbon()
    {
        return $this->hasMany(Kasbon::class);
    }

    public function gaji()
    {
        return $this->hasMany(Gaji::class);
    }

    /** Sisa utang kasbon = total ambil − total bayar. */
    public function getSisaKasbonAttribute(): float
    {
        $ambil = (float) $this->kasbon()->where('tipe', 'kasbon')->sum('jumlah');
        $bayar = (float) $this->kasbon()->where('tipe', 'bayar')->sum('jumlah');
        return max(0, $ambil - $bayar);
    }
}
