<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PenjualanHeader extends Model
{
    use HasFactory;

    protected $table = 'penjualan_headers';
    protected $primaryKey = 'internal_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'potongan_detail'  => 'array',
        'gross_settlement' => 'float',
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

    public function details()
    {
        return $this->hasMany(PenjualanDetail::class, 'internal_id', 'internal_id');
    }
}
