<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProduksiLog extends Model
{
    use HasFactory;

    protected $table = 'produksi_logs';
    protected $guarded = [];

    protected $casts = [
        'tgl_racik'   => 'date',
        'detail_text' => 'array',
    ];
}
