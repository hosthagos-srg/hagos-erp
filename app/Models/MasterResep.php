<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterResep extends Model
{
    use HasFactory;
    
    protected $table = 'master_reseps';
    protected $primaryKey = 'resep_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'ml_bibit_utama' => 'float',
        'ml_absolute'    => 'float',
        'jml_tester'     => 'float',
    ];
}
