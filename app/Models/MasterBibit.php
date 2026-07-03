<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterBibit extends Model
{
    use HasFactory;
    
    protected $table = 'master_bibits';
    protected $primaryKey = 'bibit_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'harga_per_ml' => 'float',
        'stok_ml'      => 'float',
        'threshold_ml' => 'float',
        'masuk_ml'     => 'float',
        'jual_ml'      => 'float',
        'tester_ml'    => 'float',
        'stok_awal'    => 'float',
        'nilai_masuk'  => 'float',
        'harga_awal'   => 'float',
    ];
}
