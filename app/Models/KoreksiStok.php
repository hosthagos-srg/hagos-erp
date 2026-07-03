<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class KoreksiStok extends Model
{
    use HasFactory;

    protected $table = 'koreksi_stoks';
    protected $primaryKey = 'koreksi_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'stok_sistem' => 'float',
        'stok_fisik'  => 'float',
        'selisih'     => 'float',
        'tanggal'     => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}
