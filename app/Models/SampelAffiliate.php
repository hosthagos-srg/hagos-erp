<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SampelAffiliate extends Model
{
    protected $table = 'sampel_affiliate';
    protected $guarded = [];
    protected $casts = [
        'tanggal'     => 'date',
        'hpp_satuan'  => 'float',
        'total_hpp'   => 'float',
    ];

    public function produk()
    {
        return $this->belongsTo(MasterProduk::class, 'sku_id', 'sku_id');
    }
}
