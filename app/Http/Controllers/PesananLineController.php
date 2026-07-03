<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PenjualanDetail;
use App\Models\PenjualanHeader;
use App\Models\MasterProduk;
use App\Models\MasterBibit;

/**
 * Operasi per-baris pesanan sebelum diracik:
 *  - splitBundle: pecah 1 baris bundle jadi beberapa baris aroma (mis. bundle 2×50ml → 2 botol aroma pilihan).
 *  - setMix: pasang komposisi bibit custom (mix pilihan pelanggan) ke 1 baris (disimpan di resep_blend).
 * Hanya boleh saat pesanan belum diracik (status 'Menunggu').
 */
class PesananLineController extends Controller
{
    private function guardBelumRacik(PenjualanHeader $header, PenjualanDetail $detail): void
    {
        if ($header->status_pesanan !== 'Menunggu') {
            abort(422, 'Pesanan sudah diproses/diracik — tidak bisa diubah.');
        }
        // Baris yang sudah diracik (HPP terisi, stok sudah dipotong) tidak boleh diubah lagi.
        if (!is_null($detail->hpp_satuan)) {
            abort(422, 'Baris ini sudah diracik — tidak bisa dipecah/di-mix ulang.');
        }
    }

    /**
     * Pecah bundle: 1 baris → N baris aroma. Harga bundle dialokasikan proporsional
     * ke tiap baris sesuai qty, agar total omset tetap sama. Baris asli dihapus.
     */
    public function splitBundle(Request $request, PenjualanDetail $detail)
    {
        $request->validate([
            'lines'          => 'required|array|min:1',
            'lines.*.sku_id' => 'required|string',
            'lines.*.qty'    => 'required|integer|min:1',
        ]);

        $header = PenjualanHeader::where('internal_id', $detail->internal_id)->firstOrFail();
        $this->guardBelumRacik($header, $detail);

        // Validasi SKU tujuan
        foreach ($request->lines as $ln) {
            if (!MasterProduk::where('sku_id', $ln['sku_id'])->exists()) {
                return back()->with('error', "SKU {$ln['sku_id']} tidak ditemukan di Master Produk.");
            }
        }

        $origSubtotal = (float) ($detail->subtotal ?? ((float) $detail->harga_satuan * (int) $detail->qty));
        $totalQty = array_sum(array_map(fn($l) => (int) $l['qty'], $request->lines));
        if ($totalQty < 1) return back()->with('error', 'Total qty pecahan tidak valid.');

        DB::transaction(function () use ($request, $detail, $header, $origSubtotal, $totalQty) {
            $alokasiTerpakai = 0;
            $n = count($request->lines);
            foreach ($request->lines as $i => $ln) {
                $qty = (int) $ln['qty'];
                // Baris terakhir menyerap sisa pembulatan agar Σ = subtotal asli
                if ($i === $n - 1) {
                    $sub = round($origSubtotal - $alokasiTerpakai, 2);
                } else {
                    $sub = round($origSubtotal * $qty / $totalQty, 2);
                    $alokasiTerpakai += $sub;
                }
                PenjualanDetail::create([
                    'internal_id'  => $header->internal_id,
                    'sku_id'       => $ln['sku_id'],
                    'qty'          => $qty,
                    'harga_satuan' => $qty > 0 ? round($sub / $qty, 2) : 0,
                    'subtotal'     => $sub,
                    'flag_custom'  => 1,
                    'dari_bundle'  => 1,
                ]);
            }
            $detail->delete();
        });

        return back()->with('success', 'Bundle dipecah jadi ' . count($request->lines) . ' baris aroma. Silakan racik seperti biasa.');
    }

    /** Pasang komposisi bibit custom (mix) ke 1 baris pesanan. Disimpan di resep_blend. */
    public function setMix(Request $request, PenjualanDetail $detail)
    {
        $request->validate([
            'bibit_id'   => 'required|array|min:2',
            'bibit_id.*' => 'required|string',
            'ml'         => 'required|array|min:2',
            'ml.*'       => 'required|numeric|min:0.0001',
        ]);

        $header = PenjualanHeader::where('internal_id', $detail->internal_id)->firstOrFail();
        $this->guardBelumRacik($header, $detail);

        // Gabungkan bibit sama & validasi ada di master
        $combined = [];
        foreach ($request->bibit_id as $i => $bid) {
            if (!$bid) continue;
            $ml = (float) ($request->ml[$i] ?? 0);
            if ($ml <= 0) continue;
            if (!MasterBibit::where('bibit_id', $bid)->exists()) {
                return back()->with('error', "Bibit {$bid} tidak ditemukan.");
            }
            $combined[$bid] = ($combined[$bid] ?? 0) + $ml;
        }
        if (count($combined) < 2) return back()->with('error', 'Mix custom minimal 2 bibit berbeda.');

        $blend = [];
        foreach ($combined as $bid => $ml) $blend[] = ['bibit_id' => $bid, 'ml' => $ml];

        $detail->resep_blend = json_encode($blend);
        $detail->flag_custom = 1;
        $detail->save();

        return back()->with('success', 'Komposisi mix custom (' . count($blend) . ' bibit) tersimpan. HPP & stok akan mengikuti saat racik.');
    }

    /**
     * Ubah aroma (SKU) sebuah baris — untuk memperbaiki salah pilih aroma saat pecah bundle.
     * Harga baris TETAP (alokasi bundle tidak berubah); hanya aromanya yang diganti.
     * Hanya boleh sebelum baris diracik.
     */
    public function changeAroma(Request $request, PenjualanDetail $detail)
    {
        $request->validate(['sku_id' => 'required|string']);

        $header = PenjualanHeader::where('internal_id', $detail->internal_id)->firstOrFail();
        $this->guardBelumRacik($header, $detail);

        if (!MasterProduk::where('sku_id', $request->sku_id)->exists()) {
            return back()->with('error', 'Aroma/SKU tujuan tidak ditemukan di Master Produk.');
        }

        $lama = $detail->sku_id;
        if ($request->sku_id !== $lama) {
            $detail->sku_id = $request->sku_id;
            $detail->save();
        }

        return back()->with('success', "Aroma baris diubah: {$lama} → {$request->sku_id}. Harga baris tidak berubah.");
    }

    /** Hapus komposisi mix custom dari 1 baris (kembali ke resep normal SKU). */
    public function clearMix(PenjualanDetail $detail)
    {
        $header = PenjualanHeader::where('internal_id', $detail->internal_id)->firstOrFail();
        $this->guardBelumRacik($header, $detail);
        $detail->resep_blend = null;
        $detail->flag_custom = 0;
        $detail->save();
        return back()->with('success', 'Mix custom dihapus dari baris ini.');
    }
}
