<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UtangCicilan extends Model
{
    protected $table = 'utang_cicilan';
    protected $guarded = [];
    protected $casts = ['bulan_mulai' => 'date'];

    public function sumberDana()
    {
        return $this->belongsTo(SumberDana::class);
    }

    public function pembayaran()
    {
        return $this->hasMany(CicilanPembayaran::class);
    }

    public function getSisaUtangAttribute(): float
    {
        $sudahBayar = $this->pembayaran()->where('status', 'lunas')->sum('jumlah_bayar');
        return max(0, $this->total_utang - $sudahBayar);
    }
}
