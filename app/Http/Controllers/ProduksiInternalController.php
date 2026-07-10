<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterBibit;
use App\Models\MasterKomponen;
use Illuminate\Support\Facades\DB;

class ProduksiInternalController extends Controller
{
    public function index()
    {
        $bibits = MasterBibit::where('status', 'Aktif')->get();
        $absc = MasterKomponen::where('komponen_id', 'KMP-ABSC')->first();
        $absm = MasterKomponen::where('komponen_id', 'KMP-ABSM')->first();
        $aqua = MasterKomponen::where('komponen_id', 'KMP-AQUA')->first();
        $tstr = MasterKomponen::where('komponen_id', 'KMP-TSTR')->first();

        return view('produksi.index', compact('bibits', 'absc', 'absm', 'aqua', 'tstr'));
    }

    /** Riwayat produksi internal — tab terpisah: Tester & Absolute (dari produksi_logs). */
    public function riwayat(Request $request)
    {
        $tab = $request->input('tab') === 'absolute' ? 'absolute' : 'tester';
        $tipe = $tab === 'absolute' ? 'Absolute' : 'Tester';

        $applyFilter = function ($q) use ($request, $tipe) {
            $q->where('tipe', $tipe);
            if ($request->filled('dari'))         $q->whereDate('tgl_racik', '>=', $request->dari);
            if ($request->filled('sampai'))       $q->whereDate('tgl_racik', '<=', $request->sampai);
            if ($request->filled('diracik_oleh')) $q->where('diracik_oleh', $request->diracik_oleh);
            return $q;
        };

        $items = $applyFilter(\App\Models\ProduksiLog::query())
            ->orderByDesc('tgl_racik')->orderByDesc('id')->paginate(50)->withQueryString();

        // Ringkasan (ikut filter): jumlah sesi + total unit (botol utk Tester / ml utk Absolute)
        $all = $applyFilter(\App\Models\ProduksiLog::query())->get();
        $totalSesi = $all->count();
        $totalUnit = 0.0;
        foreach ($all as $r) {
            $d = $r->detail_text ?? [];
            if ($tipe === 'Tester') {
                foreach ((array) $d as $row) $totalUnit += (float) ($row['qty'] ?? 0);
            } else {
                $totalUnit += (float) ($d['ml_absolute_dihasilkan'] ?? 0);
            }
        }

        $admins = \App\Models\MasterKategori::where('tipe_kategori', 'Admin')->orderBy('nilai')->pluck('nilai');

        return view('produksi.riwayat', compact('items', 'tab', 'tipe', 'totalSesi', 'totalUnit', 'admins'));
    }

    /**
     * Parser angka untuk INPUT dari form (bisa "122,5" atau "122.5"). Kolom DB sudah numerik.
     */
    private function parseNumber($str) {
        if ($str === null || $str === '') return 0;
        $str = (string) $str;
        $str = preg_replace('/[^0-9.,\-]/', '', $str);
        $lastDot = strrpos($str, '.');
        $lastComma = strrpos($str, ',');
        if ($lastDot !== false && $lastComma !== false) {
            if ($lastDot > $lastComma) { $str = str_replace(',', '', $str); }
            else { $str = str_replace('.', '', $str); $str = str_replace(',', '.', $str); }
        } elseif ($lastComma !== false) {
            $str = str_replace(',', '.', $str);
        }
        return (float) $str;
    }

    public function storeAbsolute(Request $request)
    {
        $request->validate([
            'ml_murni' => 'required|numeric|min:1',
            'ml_denat' => 'required|numeric|min:0',
        ]);

        try {
            $warnings = [];
            DB::transaction(function () use ($request, &$warnings) {
                $mlMurni = $request->ml_murni;
                $mlDenat = $request->ml_denat;
                $mlTotal = $mlMurni + $mlDenat;

                $absc = MasterKomponen::where('komponen_id', 'KMP-ABSC')->firstOrFail();
                $absm = MasterKomponen::where('komponen_id', 'KMP-ABSM')->firstOrFail();
                $aqua = MasterKomponen::where('komponen_id', 'KMP-AQUA')->firstOrFail();

                // Stok kurang: produksi tetap lanjut, tapi kumpulkan peringatan (stok bisa jadi minus)
                if ((float) $absm->stok < $mlMurni) {
                    $warnings[] = "Stok Absolute Murni (" . ($absm->nama_komponen ?? 'KMP-ABSM') . ") kurang: sisa {$absm->stok}ml, butuh {$mlMurni}ml — stok jadi minus.";
                }
                if ((float) $aqua->stok < $mlDenat) {
                    $warnings[] = "Stok Aquades/Denat (" . ($aqua->nama_komponen ?? 'KMP-AQUA') . ") kurang: sisa {$aqua->stok}ml, butuh {$mlDenat}ml — stok jadi minus.";
                }

                // Deduct materials
                $absm->stok -= $mlMurni;
                $absm->save();

                $aqua->stok -= $mlDenat;
                $aqua->save();

                // Moving Average HPP untuk Absolute Campuran.
                // Pakai stok efektif (tidak negatif) agar rata-rata tidak rusak bila stok minus.
                $oldStok = max(0, (float) $absc->stok);
                $oldHpp = (float) $absc->harga_satuan;
                $oldValue = $oldStok * $oldHpp;

                $hppMurni = (float) $absm->harga_satuan;
                $hppAqua = (float) $aqua->harga_satuan;
                $addedValue = ($mlMurni * $hppMurni) + ($mlDenat * $hppAqua);

                // Moving average hanya bila stok lama valid & bernilai (harga > 0); jika tidak,
                // pakai biaya per-ml batch ini — cegah dilusi harga ke angka tak wajar.
                $batchUnit = $mlTotal > 0 ? ($addedValue / $mlTotal) : 0;
                if ($oldStok > 0 && $oldHpp > 0) {
                    $newHpp = ($oldValue + $addedValue) / ($oldStok + $mlTotal);
                } else {
                    $newHpp = $batchUnit;
                }

                // Naikkan stok riil (boleh dari nilai minus sebelumnya) & update HPP
                $absc->stok = (float) $absc->stok + $mlTotal;
                $absc->harga_satuan = round($newHpp, 2);
                $absc->save();

                \App\Models\ProduksiLog::create([
                    'tgl_racik' => $request->input('tgl_racik', now()->toDateString()),
                    'diracik_oleh' => $request->input('diracik_oleh', 'Admin'),
                    'tipe' => 'Absolute',
                    'ringkasan' => 'Racik Absolute Campuran: ' . rtrim(rtrim(number_format($mlTotal, 2, '.', ''), '0'), '.') . ' ml (murni ' . $mlMurni . ' + denat ' . $mlDenat . ')',
                    'detail_text' => [
                        'ml_murni_digunakan' => $mlMurni,
                        'ml_denat_digunakan' => $mlDenat,
                        'ml_absolute_dihasilkan' => $mlTotal,
                    ],
                ]);
            });

            $msg = 'Berhasil meracik Absolute Campuran!';
            if (! empty($warnings)) $msg .= ' ⚠ ' . implode(' ', $warnings);
            return redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal meracik: ' . $e->getMessage());
        }
    }

    public function storeTester(Request $request)
    {
        $request->validate([
            'tgl_racik' => 'required|date',
            'diracik_oleh' => 'required|string',
            'aromas' => 'required|array',
            'aromas.*.bibit_id' => 'required|string',
            'aromas.*.qty_botol' => 'required|integer|min:1',
        ]);

        try {
            $warnings = [];
            DB::transaction(function () use ($request, &$warnings) {
                $absc = MasterKomponen::where('komponen_id', 'KMP-ABSC')->firstOrFail();
                $btl3 = MasterKomponen::where('komponen_id', 'KMP-BTL3')->firstOrFail();
                $stkt = MasterKomponen::where('komponen_id', 'KMP-STKT')->firstOrFail();
                $tstr = MasterKomponen::where('komponen_id', 'KMP-TSTR')->firstOrFail();

                $totalQty = 0;
                $totalBibitValue = 0;
                $logDetails = [];

                foreach ($request->aromas as $aroma) {
                    $bibitId = $aroma['bibit_id'];
                    $qty = $aroma['qty_botol'];
                    $totalQty += $qty;

                    $mlBibitUsed = 1.5 * $qty;
                    $bibit = MasterBibit::where('bibit_id', $bibitId)->firstOrFail();

                    // Deduct bibit M1
                    $bibitStok = (float) $bibit->stok_ml;
                    if ($bibitStok < $mlBibitUsed) {
                        throw new \Exception("Stok bibit {$bibit->nama_bibit} tidak mencukupi! Sisa: {$bibitStok}ml, Butuh: {$mlBibitUsed}ml");
                    }
                    $bibit->stok_ml = $bibitStok - $mlBibitUsed;
                    $bibit->save();

                    $hppBibit = (float) $bibit->harga_per_ml;
                    $totalBibitValue += ($mlBibitUsed * $hppBibit);

                    $logDetails[] = [
                        'bibit_id' => $bibitId,
                        'nama_bibit' => $bibit->nama_bibit,
                        'qty' => $qty
                    ];
                }

                $totalAbsUsed = 1.5 * $totalQty;

                // Stok kurang: produksi tetap lanjut, tapi kumpulkan peringatan (stok bisa jadi minus)
                if ((float) $absc->stok < $totalAbsUsed) {
                    $warnings[] = "Stok Absolute Campuran (" . ($absc->nama_komponen ?? 'KMP-ABSC') . ") kurang: sisa {$absc->stok}ml, butuh {$totalAbsUsed}ml — stok jadi minus.";
                }
                if ((float) $btl3->stok < $totalQty) {
                    $warnings[] = "Stok botol tester (" . ($btl3->nama_komponen ?? 'KMP-BTL3') . ") kurang: sisa {$btl3->stok}, butuh {$totalQty} — stok jadi minus.";
                }
                // Stiker tester = konsumabel (track_stok=Tidak), sama seperti Stiker Utama:
                // tak dilacak, tak dipotong, tak boleh jadi minus. Warning HANYA bila memang dilacak.
                if ($stkt->track_stok === 'Ya' && (float) $stkt->stok < $totalQty) {
                    $warnings[] = "Stok stiker tester (" . ($stkt->nama_komponen ?? 'KMP-STKT') . ") kurang: sisa {$stkt->stok}, butuh {$totalQty} — stok jadi minus.";
                }

                // Deduct components M5
                $absc->stok -= $totalAbsUsed;
                $absc->save();

                $btl3->stok -= $totalQty;
                $btl3->save();

                // Potong stok stiker tester HANYA bila dilacak (biaya tetap masuk HPP di bawah).
                if ($stkt->track_stok === 'Ya') {
                    $stkt->stok -= $totalQty;
                    $stkt->save();
                }

                // Moving Average HPP untuk Botol Tester Jadi.
                // Pakai stok efektif (tidak negatif) agar rata-rata tidak rusak bila stok minus.
                $oldStok = max(0, (float) $tstr->stok);
                $oldHpp = (float) $tstr->harga_satuan;
                $oldValue = $oldStok * $oldHpp;

                $hppAbsc = (float) $absc->harga_satuan;
                $hppBtl = (float) $btl3->harga_satuan;
                $hppStk = (float) $stkt->harga_satuan;

                $addedValue = $totalBibitValue + ($totalAbsUsed * $hppAbsc) + ($totalQty * $hppBtl) + ($totalQty * $hppStk);

                // Biaya per botol batch ini.
                $batchUnit = $totalQty > 0 ? ($addedValue / $totalQty) : 0;
                // Moving average HANYA bila stok lama valid & bernilai (harga > 0). Jika tidak,
                // pakai biaya batch — cegah harga jatuh ke angka kecil (mis. Rp 8) karena dilusi
                // terhadap stok lama yang harganya 0/tidak valid.
                if ($oldStok > 0 && $oldHpp > 0) {
                    $newHpp = ($oldValue + $addedValue) / ($oldStok + $totalQty);
                } else {
                    $newHpp = $batchUnit;
                }

                // Naikkan stok riil & update HPP
                $tstr->stok = (float) $tstr->stok + $totalQty;
                $tstr->harga_satuan = round($newHpp, 2);
                $tstr->save();

                $namaAroma = collect($logDetails)->map(fn($d) => ($d['nama_bibit'] ?? $d['bibit_id']) . ' ×' . $d['qty'])->implode(', ');
                \App\Models\ProduksiLog::create([
                    'tgl_racik' => $request->tgl_racik,
                    'diracik_oleh' => $request->diracik_oleh,
                    'tipe' => 'Tester',
                    'ringkasan' => 'Racik Tester: ' . $totalQty . ' botol (' . $namaAroma . ')',
                    'detail_text' => $logDetails,
                ]);
            });

            $msg = 'Berhasil meracik Botol Tester!';
            if (! empty($warnings)) $msg .= ' ⚠ ' . implode(' ', $warnings);
            return redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal meracik: ' . $e->getMessage());
        }
    }

    public function checkStock(Request $request)
    {
        $aromas = $request->input('aromas', []);
        $usage = [];
        $totalBotol = 0;

        foreach ($aromas as $aroma) {
            $bibitId = $aroma['bibit_id'] ?? null;
            $qty = (int) ($aroma['qty_botol'] ?? 0);
            if (!$bibitId || !$qty) continue;
            $totalBotol += $qty;

            $mlBibitUsed = 1.5 * $qty;
            if (!isset($usage[$bibitId])) {
                $bibit = \App\Models\MasterBibit::where('bibit_id', $bibitId)->first();
                $usage[$bibitId] = ['type' => 'bibit', 'item_id' => $bibitId, 'nama_bibit' => $bibit ? $bibit->nama_bibit : $bibitId, 'stok_sistem' => $bibit ? (float) $bibit->stok_ml : 0, 'total_butuh' => 0];
            }
            $usage[$bibitId]['total_butuh'] += $mlBibitUsed;
        }

        // Botol tester (KMP-BTL3) — 1 per botol tester
        if ($totalBotol > 0) {
            $btl = \App\Models\MasterKomponen::where('komponen_id', 'KMP-BTL3')->first();
            if ($btl) {
                $usage['KMP-BTL3'] = ['type' => 'komponen', 'item_id' => 'KMP-BTL3', 'nama_bibit' => $btl->nama_komponen, 'stok_sistem' => (float) $btl->stok, 'total_butuh' => $totalBotol];
            }
        }

        $deficits = [];
        foreach ($usage as $u) {
            if ($u['total_butuh'] > $u['stok_sistem']) $deficits[] = $u;
        }

        return response()->json(count($deficits) > 0 ? ['status' => 'deficit', 'deficits' => $deficits] : ['status' => 'ok']);
    }

    public function adjustStock(Request $request)
    {
        $adjustments = $request->input('adjustments', []);
        
        \Illuminate\Support\Facades\DB::transaction(function() use ($adjustments) {
            foreach ($adjustments as $adj) {
                $type = $adj['type'] ?? 'bibit';
                $itemId = $adj['item_id'] ?? ($adj['bibit_id'] ?? null);
                if (!$itemId) continue;
                $stokFisik = $this->parseNumber($adj['real_stock'] ?? 0);

                if ($type === 'komponen') {
                    $item = \App\Models\MasterKomponen::where('komponen_id', $itemId)->first();
                    if (!$item) continue;
                    $stokSistem = (float) $item->stok;
                    $nama = $item->nama_komponen;
                } else {
                    $item = \App\Models\MasterBibit::where('bibit_id', $itemId)->first();
                    if (!$item) continue;
                    $stokSistem = (float) $item->stok_ml;
                    $nama = $item->nama_bibit;
                }

                // T12 — catat koreksi stok
                \App\Models\KoreksiStok::create([
                    'tanggal' => now()->toDateString(),
                    'item_type' => $type,
                    'item_id' => $itemId,
                    'nama_item' => $nama,
                    'stok_sistem' => $stokSistem,
                    'stok_fisik' => $stokFisik,
                    'selisih' => $stokFisik - $stokSistem,
                    'alasan' => $adj['alasan'] ?? 'Penyesuaian saat produksi',
                    'dicatat_oleh' => $adj['dicatat_oleh'] ?? null,
                ]);

                if ($type === 'komponen') { $item->stok = $stokFisik; }
                else { $item->stok_ml = $stokFisik; }
                $item->save();
            }
        });

        return response()->json(['status' => 'ok']);
    }
}
