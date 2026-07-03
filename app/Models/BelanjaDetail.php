<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BelanjaDetail extends Model
{
    use HasFactory;

    protected $table = 'belanja_details';
    protected $primaryKey = 'batch_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'qty'                => 'float',
        'harga_total_item'   => 'float',
        'harga_net_per_unit' => 'float',
        'stok_sisa'          => 'float',
    ];

    public function header()
    {
        return $this->belongsTo(BelanjaHeader::class, 'belanja_id', 'belanja_id');
    }
}
