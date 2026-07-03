<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StokJadiLog extends Model
{
    use HasFactory;

    protected $table = 'stok_jadi_logs';
    protected $primaryKey = 'log_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'hpp_per_unit' => 'float',
        'qty'          => 'integer',
        'tanggal'      => 'date',
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

    /** Helper pencatatan pergerakan stok jadi. */
    public static function catat(string $skuId, string $tipe, int $qty, string $sumber, ?float $hpp = null, ?string $refId = null, ?string $oleh = null, ?string $catatan = null): void
    {
        static::create([
            'tanggal'      => now()->toDateString(),
            'sku_id'       => $skuId,
            'tipe'         => $tipe,
            'qty'          => $qty,
            'sumber'       => $sumber,
            'hpp_per_unit' => $hpp,
            'ref_id'       => $refId,
            'dicatat_oleh' => $oleh,
            'catatan'      => $catatan,
        ]);
    }
}
