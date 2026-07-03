<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceSku extends Model
{
    protected $fillable = [
        'platform',
        'marketplace_nama',
        'marketplace_variasi',
        'sku_id'
    ];

    public function produk()
    {
        return $this->belongsTo(MasterProduk::class, 'sku_id', 'sku_id');
    }
}
