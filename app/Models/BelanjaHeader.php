<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BelanjaHeader extends Model
{
    use HasFactory;

    protected $table = 'belanja_headers';
    protected $primaryKey = 'belanja_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'tgl_belanja'     => 'date',
        'subtotal_kotor'  => 'float',
        'voucher_nominal' => 'float',
        'ongkir_net'      => 'float',
        'biaya_layanan'   => 'float',
        'total_bayar'     => 'float',
        'stok_diterapkan' => 'boolean',
    ];

    public function details()
    {
        return $this->hasMany(BelanjaDetail::class, 'belanja_id', 'belanja_id');
    }
}
