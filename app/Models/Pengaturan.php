<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $table = 'pengaturan';
    protected $guarded = [];

    public const KUNCI_TGL = 'tgl_kunci_buku';

    public static function get(string $kunci, $default = null)
    {
        return static::where('kunci', $kunci)->value('nilai') ?? $default;
    }

    public static function set(string $kunci, $nilai): void
    {
        static::updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
    }

    /** Tanggal kunci buku (Y-m-d) atau null bila tidak diset. */
    public static function tanggalKunci(): ?string
    {
        $v = static::get(self::KUNCI_TGL);
        return $v ?: null;
    }

    /**
     * Cek apakah $tanggal jatuh di periode terkunci (<= tanggal kunci).
     * Mengembalikan pesan error bila terkunci, atau null bila boleh.
     */
    public static function cekTerkunci(?string $tanggal): ?string
    {
        $kunci = static::tanggalKunci();
        if (! $kunci || ! $tanggal) return null;

        if (\Carbon\Carbon::parse($tanggal)->lte(\Carbon\Carbon::parse($kunci))) {
            return 'Periode sudah dikunci sampai ' . \Carbon\Carbon::parse($kunci)->format('d M Y')
                . '. Transaksi tanggal ' . \Carbon\Carbon::parse($tanggal)->format('d M Y') . ' tidak diizinkan.';
        }
        return null;
    }
}
