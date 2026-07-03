<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterProduk extends Model
{
    use HasFactory;
    
    protected $table = 'master_produks';
    protected $primaryKey = 'sku_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'hpp_botol' => 'float',
        'ukuran_ml' => 'integer',
        'stok_t11'  => 'integer',
        'hpp_t11'   => 'float',
    ];

    /**
     * Tambah stok produk jadi (T11) dengan HPP "botol telanjang" rata-rata bergerak.
     */
    public function tambahStokJadi(int $qty, float $hppBarePerUnit): void
    {
        if ($qty <= 0) return;
        $oldStok = (int) $this->stok_t11;
        $oldValue = $oldStok * (float) $this->hpp_t11;
        $newStok = $oldStok + $qty;
        $this->hpp_t11 = $newStok > 0 ? round(($oldValue + ($qty * $hppBarePerUnit)) / $newStok, 2) : 0;
        $this->stok_t11 = $newStok;
        $this->save();
    }
}
