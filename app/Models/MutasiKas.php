<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MutasiKas extends Model
{
    use HasFactory;

    protected $table = 'mutasi_kas';
    protected $primaryKey = 'mutasi_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'jumlah'  => 'float',
        'tanggal' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
            // Period lock (tutup buku): tolak transaksi kas di periode terkunci.
            $tgl = $model->tanggal ? \Carbon\Carbon::parse($model->tanggal)->toDateString() : null;
            if ($pesan = \App\Models\Pengaturan::cekTerkunci($tgl)) {
                throw new \App\Exceptions\PeriodeTerkunciException($pesan);
            }
        });
    }

    /** Catat mutasi kas. $jumlah selalu positif; arah ditentukan $tipe. */
    public static function catat(string $akun, string $tipe, float $jumlah, string $kategori, ?string $refId = null, ?string $keterangan = null, ?string $oleh = null, ?string $tanggal = null): void
    {
        if (empty($akun) || $jumlah == 0) return;
        static::create([
            'tanggal'      => $tanggal ?? now()->toDateString(),
            'akun'         => $akun,
            'tipe'         => $tipe,
            'jumlah'       => abs($jumlah),
            'kategori'     => $kategori,
            'ref_id'       => $refId,
            'keterangan'   => $keterangan,
            'dicatat_oleh' => $oleh,
        ]);
    }
}
