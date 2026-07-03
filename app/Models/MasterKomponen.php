<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterKomponen extends Model
{
    use HasFactory;
    
    protected $table = 'master_komponens';
    protected $primaryKey = 'komponen_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'harga_satuan' => 'float',
        'stok'         => 'float',
    ];
}
