<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PenjualanDetail extends Model
{
    use HasFactory;

    protected $table = 'penjualan_details';
    protected $primaryKey = 'detail_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function header()
    {
        return $this->belongsTo(PenjualanHeader::class, 'internal_id', 'internal_id');
    }

    public function produk()
    {
        return $this->belongsTo(MasterProduk::class, 'sku_id', 'sku_id');
    }
}
