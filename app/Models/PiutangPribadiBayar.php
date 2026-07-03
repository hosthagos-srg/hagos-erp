<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PiutangPribadiBayar extends Model
{
    use HasFactory;

    protected $table = 'piutang_pribadi_bayar';
    protected $guarded = [];

    protected $casts = [
        'jumlah'    => 'decimal:2',
        'tgl_bayar' => 'date',
    ];

    public function piutang()
    {
        return $this->belongsTo(PiutangPribadi::class, 'piutang_pribadi_id');
    }
}
