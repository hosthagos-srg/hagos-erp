<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UtangPribadi extends Model
{
    use HasFactory;

    protected $table = 'utang_pribadi';
    protected $guarded = [];

    protected $casts = [
        'jumlah_pinjaman' => 'decimal:2',
        'tgl_pinjam'      => 'date',
    ];

    public function bayar()
    {
        return $this->hasMany(UtangPribadiBayar::class, 'utang_pribadi_id');
    }

    /** Total sudah dibayar (dicicil). */
    public function getTotalBayarAttribute(): float
    {
        return (float) $this->bayar->sum('jumlah');
    }

    /** Sisa utang (belum dibayar). */
    public function getSisaAttribute(): float
    {
        return (float) $this->jumlah_pinjaman - $this->total_bayar;
    }
}
