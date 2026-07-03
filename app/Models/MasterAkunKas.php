<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MasterAkunKas extends Model
{
    use HasFactory;

    protected $table = 'master_akun_kas';
    protected $primaryKey = 'akun_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    /**
     * Di seluruh sistem, akun diikat lewat NAMA (mutasi_kas.akun, penjualan_headers.akun_masuk),
     * bukan akun_id. Maka saat nama akun diganti, cascade ke semua riwayat dalam satu transaksi
     * agar saldo & laporan tidak putus (mencegah riwayat yatim).
     */
    protected static function booted(): void
    {
        static::updating(function (MasterAkunKas $akun) {
            if ($akun->isDirty('nama_akun')) {
                $lama = $akun->getOriginal('nama_akun');
                $baru = $akun->nama_akun;
                DB::transaction(function () use ($lama, $baru) {
                    DB::table('mutasi_kas')->where('akun', $lama)->update(['akun' => $baru]);
                    DB::table('penjualan_headers')->where('akun_masuk', $lama)->update(['akun_masuk' => $baru]);
                });
            }
        });
    }
}
