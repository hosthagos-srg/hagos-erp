<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kasbon extends Model
{
    protected $table = 'kasbon';
    protected $guarded = [];
    protected $casts = [
        'tanggal' => 'date',
        'jumlah'  => 'float',
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }
}
