<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterHarga extends Model
{
    use HasFactory;
    
    protected $table = 'master_hargas';
    protected $primaryKey = 'harga_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'harga_jual' => 'float',
    ];
}
