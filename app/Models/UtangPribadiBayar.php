<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UtangPribadiBayar extends Model
{
    use HasFactory;

    protected $table = 'utang_pribadi_bayar';
    protected $guarded = [];

    protected $casts = [
        'jumlah'    => 'decimal:2',
        'tgl_bayar' => 'date',
    ];

    public function utang()
    {
        return $this->belongsTo(UtangPribadi::class, 'utang_pribadi_id');
    }
}
