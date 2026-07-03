<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PiutangPribadi extends Model
{
    use HasFactory;

    protected $table = 'piutang_pribadi';
    protected $guarded = [];

    protected $casts = [
        'jumlah_pinjaman' => 'decimal:2',
        'tgl_pinjam'      => 'date',
    ];

    public function bayar()
    {
        return $this->hasMany(PiutangPribadiBayar::class, 'piutang_pribadi_id');
    }

    /** Total sudah dikembalikan. */
    public function getTotalBayarAttribute(): float
    {
        return (float) $this->bayar->sum('jumlah');
    }

    /** Sisa piutang (belum dikembalikan). */
    public function getSisaAttribute(): float
    {
        return (float) $this->jumlah_pinjaman - $this->total_bayar;
    }
}
