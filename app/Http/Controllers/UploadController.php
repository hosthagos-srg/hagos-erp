<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PenjualanHeader;
use App\Models\PenjualanDetail;
use App\Models\MasterProduk;
use App\Models\MarketplaceSku;
use OpenSpout\Reader\CSV\Reader as CSVReader;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;

class UploadController extends Controller
{
    public function index()
    {
        return view('upload.index');
    }

    private function getReader($extension)
    {
        if (strtolower($extension) === 'csv') {
            return new CSVReader();
        } elseif (strtolower($extension) === 'xlsx') {
            return new XLSXReader();
        }
        throw new \Exception("Unsupported file type: $extension");
    }

    private function parseNumber($str) {
        if (empty($str)) return 0;
        $str = (string) $str;
        
        $str = preg_replace('/[^0-9\.,\-]/', '', $str);

        $dotPos = strrpos($str, '.');
        $commaPos = strrpos($str, ',');

        if ($dotPos !== false && $commaPos !== false) {
            if ($dotPos > $commaPos) {
                $str = str_replace(',', '', $str);
            } else {
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            }
        } elseif ($commaPos !== false) {
            $parts = explode(',', $str);
            if (strlen(end($parts)) == 3) {
                $str = str_replace(',', '', $str);
            } else {
                $str = str_replace(',', '.', $str);
            }
        } elseif ($dotPos !== false) {
            $parts = explode('.', $str);
            if (strlen(end($parts)) == 3) {
                $str = str_replace('.', '', $str);
            }
        }

        return (float) $str;
    }

    /**
     * Ubah string tanggal dari file marketplace ke format Y-m-d.
     * TikTok: "31/05/2026 21:56:16" (d/m/Y). Shopee: "2026-05-01 12:34".
     * Mengembalikan null bila tidak bisa diparse.
     */
    private function parseDate($raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') return null;

        foreach (['d/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d', 'd-m-Y H:i:s', 'd-m-Y'] as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $raw);
            if ($dt !== false) {
                return $dt->format('Y-m-d');
            }
        }
        $ts = strtotime($raw);
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    private function findSku($platform, $productName, $variation) {
        // 1. Cek di kamus mapping
        $mapping = MarketplaceSku::where('platform', $platform)
                    ->where('marketplace_nama', $productName)
                    ->where('marketplace_variasi', $variation)
                    ->first();
        
        if ($mapping && $mapping->sku_id) {
            return $mapping->sku_id;
        }

        // 2. Jika belum ada di kamus, coba auto-mapping (fallback), kalau ketemu simpan
        $products = MasterProduk::all();
        $matchedSku = null;
        foreach ($products as $p) {
            $nameMatch = stripos($productName, $p->nama_produk) !== false;
            $sizeMatch = stripos($variation, (string)$p->ukuran_ml) !== false || stripos($productName, (string)$p->ukuran_ml) !== false;
            if ($nameMatch && $sizeMatch) {
                $matchedSku = $p->sku_id;
                break;
            }
        }

        // 3. Simpan ke kamus (baik ketemu maupun tidak) agar user bisa mereview/memperbaiki nanti
        if (!$mapping) {
            MarketplaceSku::create([
                'platform' => $platform,
                'marketplace_nama' => $productName,
                'marketplace_variasi' => $variation,
                'sku_id' => $matchedSku, // bisa null
            ]);
        } else if (!$mapping->sku_id && $matchedSku) {
            $mapping->update(['sku_id' => $matchedSku]);
        }

        return $matchedSku;
    }

    /**
     * Cari indeks kolom dari beberapa kemungkinan nama header (case-insensitive, tahan variasi).
     * TikTok memakai "Tracking ID", Shopee "No. Resi" — nama bisa beda antar versi export.
     */
    private function findCol(array $colMap, array $candidates)
    {
        $lower = [];
        foreach ($colMap as $name => $idx) $lower[strtolower(trim((string) $name))] = $idx;
        foreach ($candidates as $c) {
            $key = strtolower(trim($c));
            if (array_key_exists($key, $lower)) return $lower[$key];
        }
        return null;
    }

    private function resiCandidates(string $platform): array
    {
        if ($platform === 'TikTok') {
            return ['Tracking ID', 'Tracking Number', 'Tracking No.', 'Tracking No', 'AWB', 'Waybill Number', 'Shipping Provider Tracking Number'];
        }
        // Shopee
        return ['No. Resi', 'No Resi', 'Nomor Resi', 'Resi', 'No. Resi*', 'Tracking Number*', 'Tracking Number'];
    }

    public function processPesanan(Request $request)
    {
        $request->validate([
            // Pakai extensions (cek ekstensi nama file), BUKAN mimes: file .xlsx TikTok
            // terdeteksi kontennya sebagai application/zip → mimes menolaknya diam-diam.
            'file_pesanan' => 'required|file|extensions:csv,txt,xlsx,xls',
            'platform' => 'required|string',
        ]);

        $file = $request->file('file_pesanan');
        $extension = $file->getClientOriginalExtension();
        $path = $file->getPathname();

        $reader = $this->getReader($extension);
        $reader->open($path);

        $platform = $request->platform; // 'Shopee' or 'TikTok'
        $channelName = 'Marketplace ' . $platform;

        $countSaved = 0;
        $countDuplicate = 0;
        $countSkipExisting = 0;
        $unmatched = [];
        $createdThisRun = []; // internal_id pesanan yang DIBUAT di run upload ini (guard upload ulang)

        DB::beginTransaction();
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $isHeader = true;
                $colMap = [];

                foreach ($sheet->getRowIterator() as $row) {
                    $cells = $row->toArray();
                    
                    if ($isHeader) {
                        foreach ($cells as $index => $colName) {
                            $colMap[trim((string)$colName)] = $index;
                        }
                        $isHeader = false;
                        continue;
                    }

                    // Mapping for TikTok vs Shopee
                    if ($platform === 'TikTok') {
                        $orderId = isset($colMap['Order ID']) ? trim((string)($cells[$colMap['Order ID']] ?? '')) : null;
                        $status = isset($colMap['Order Status']) ? trim((string)($cells[$colMap['Order Status']] ?? '')) : '';
                        $productName = isset($colMap['Product Name']) ? trim((string)($cells[$colMap['Product Name']] ?? '')) : '';
                        $variationRaw = isset($colMap['Variation']) ? trim((string)($cells[$colMap['Variation']] ?? '')) : '';
                        $qty = isset($colMap['Quantity']) ? (int)($cells[$colMap['Quantity']] ?? 1) : 1;
                        $price = isset($colMap['SKU Subtotal After Discount']) ? $this->parseNumber($cells[$colMap['SKU Subtotal After Discount']] ?? 0) : 0;
                        $buyerName = isset($colMap['Buyer Username']) ? trim((string)($cells[$colMap['Buyer Username']] ?? '')) : '';
                        $tglPesanan = isset($colMap['Created Time']) ? $this->parseDate($cells[$colMap['Created Time']] ?? '') : null;
                        $resiIdx = $this->findCol($colMap, $this->resiCandidates('TikTok'));
                        $noResi = $resiIdx !== null ? trim((string)($cells[$resiIdx] ?? '')) : null;
                    } else { // Shopee
                        $orderId = isset($colMap['No. Pesanan']) ? trim((string)($cells[$colMap['No. Pesanan']] ?? '')) : null;
                        $status = isset($colMap['Status Pesanan']) ? trim((string)($cells[$colMap['Status Pesanan']] ?? '')) : '';
                        $productName = isset($colMap['Nama Produk']) ? trim((string)($cells[$colMap['Nama Produk']] ?? '')) : '';
                        $variationRaw = isset($colMap['Nama Variasi']) ? trim((string)($cells[$colMap['Nama Variasi']] ?? '')) : '';
                        $qty = isset($colMap['Jumlah']) ? (int)($cells[$colMap['Jumlah']] ?? 1) : 1;
                        $price = isset($colMap['Total Pembayaran']) ? $this->parseNumber($cells[$colMap['Total Pembayaran']] ?? 0) : 0;
                        $buyerName = isset($colMap['Username (Pembeli)']) ? trim((string)($cells[$colMap['Username (Pembeli)']] ?? '')) : '';
                        $tglPesanan = isset($colMap['Waktu Pesanan Dibuat']) ? $this->parseDate($cells[$colMap['Waktu Pesanan Dibuat']] ?? '') : null;
                        $resiIdx = $this->findCol($colMap, $this->resiCandidates('Shopee'));
                        $noResi = $resiIdx !== null ? trim((string)($cells[$resiIdx] ?? '')) : null;
                    }

                    // Bersihkan variasi (hanya ambil angka + ml) agar tidak terpisah karena "Random/Request"
                    if (preg_match('/(\d+\s*ml)/i', $variationRaw, $matches)) {
                        $variation = strtoupper(str_replace(' ', '', $matches[1])); // Hasil: "30ML" atau "50ML"
                    } else {
                        $variation = $variationRaw;
                    }

                    if (empty($orderId)) continue;
                    
                    // Skip cancelled orders for now
                    if (stripos($status, 'batal') !== false || stripos($status, 'cancel') !== false) {
                        continue;
                    }

                    // Find SKU
                    $skuId = $this->findSku($platform, $productName, $variation);
                    
                    if ($skuId === 'SKIP') {
                        continue; // Produk discontinue/diabaikan selamanya
                    }

                    if (!$skuId) {
                        $unmatched[] = "Order $orderId: $productName ($variation)";
                        continue;
                    }

                    // Create or Update Header
                    $header = PenjualanHeader::where('external_order_id', $orderId)->first();
                    if (!$header) {
                        $header = PenjualanHeader::create([
                            'external_order_id' => $orderId,
                            'no_resi' => ($noResi !== '' ? $noResi : null),
                            'channel' => $channelName,
                            'metode_pengiriman' => 'Dikirim', // pesanan marketplace selalu dikirim
                            'tgl_pesanan' => $tglPesanan ?? now()->toDateString(),
                            'status_pesanan' => 'Menunggu', // goes to racik
                            'status_pembayaran' => 'Belum Cair',
                            'gmv_kotor' => $price, // Simplified for prototype
                            'nama_pembeli' => $buyerName,
                        ]);
                        $createdThisRun[$header->internal_id] = true;
                    } else {
                        // Header sudah ada. Lengkapi resi bila belum terisi (aman, informatif).
                        if (empty($header->no_resi) && !empty($noResi)) {
                            $header->no_resi = $noResi;
                            $header->save();
                        }
                    }

                    // GUARD UPLOAD ULANG: hanya tambah/akumulasi baris untuk pesanan yang DIBUAT di
                    // run upload ini. Pesanan yang sudah ada dari upload sebelumnya JANGAN disentuh —
                    // bisa jadi sudah dipecah (bundle → anak; SKU induk BUNDLE dihapus di splitBundle)
                    // atau di-set mix — sehingga menambah lagi = baris & GMV DOBEL.
                    if (!isset($createdThisRun[$header->internal_id])) {
                        $countSkipExisting++;
                        continue;
                    }

                    // Create Detail if not exist
                    $detailExists = PenjualanDetail::where('internal_id', $header->internal_id)
                                        ->where('sku_id', $skuId)
                                        ->exists();
                    
                    if (!$detailExists) {
                        PenjualanDetail::create([
                            'internal_id' => $header->internal_id,
                            'sku_id' => $skuId,
                            'qty' => $qty,
                            'harga_satuan' => $qty > 0 ? $price / $qty : 0,
                            'subtotal' => $price,
                            'flag_swap' => 0,
                        ]);
                        // Akumulasi GMV untuk pesanan multi-produk (baris ke-2 dst.)
                        if ($header->wasRecentlyCreated === false) {
                            $header->gmv_kotor = (float) $header->gmv_kotor + $price;
                            $header->save();
                        }
                        $countSaved++;
                    } else {
                        $countDuplicate++;
                    }
                }
                break; // Only read first sheet
            }
            DB::commit();

            $msg = "Berhasil mengimpor $countSaved baris pesanan baru. ";
            if ($countDuplicate > 0) {
                $msg .= "($countDuplicate baris dilewati karena duplikat). ";
            }
            if ($countSkipExisting > 0) {
                $msg .= "($countSkipExisting baris dilewati karena pesanannya sudah ada — tak diubah, aman untuk upload ulang). ";
            }
            if (count($unmatched) > 0) {
                $msg .= "Terdapat " . count($unmatched) . " produk yang tidak dikenali SKU-nya.";
                session()->flash('unmatched_skus', true);
            }
            return redirect()->back()->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }
    }

    public function processSettlement(Request $request)
    {
        $request->validate([
            // extensions (bukan mimes): xlsx TikTok terdeteksi application/zip → mimes menolak.
            'file_settlement' => 'required|file|extensions:csv,txt,xlsx,xls',
            'platform' => 'required|string',
        ]);

        $file = $request->file('file_settlement');
        $extension = $file->getClientOriginalExtension();
        $path = $file->getPathname();

        $reader = $this->getReader($extension);
        $reader->open($path);

        $platform = $request->platform;

        $headersFound = [];
        $netByOrder = []; // akumulasi: 1 pesanan bisa punya beberapa baris settlement (mis. biaya iklan/LIVE)
        $grossByOrder = []; // omset/subtotal otoritatif dari settlement (untuk hitung potongan = gross - net)
        $feeByOrder = []; // rincian potongan per pesanan: [orderId][nama biaya] => jumlah (bertanda)
        $dateByOrder = []; // F1: tanggal rilis dana per pesanan (dari file, bukan tgl upload)
        $afiliasiByOrder = []; // ADITIF (TikTok): komisi afiliasi per pesanan — hanya utk PENANDA afiliasi.
                               // TIDAK dipakai di net/margin/HPP (net_settlement sudah otoritatif).

        // Kolom OMSET (subtotal/pendapatan) otoritatif per platform
        $grossCols = $platform === 'TikTok'
            ? ['Total Pendapatan']
            : ['Harga Asli Produk', 'Total Diskon Produk']; // Shopee: dijumlah (diskon negatif)

        // ALLOWLIST kolom biaya TOP-LEVEL (hindari kolom induk/anak/duplikat yang menggandakan).
        $feeCols = $platform === 'TikTok'
            ? ['Biaya komisi platform', 'Ongkir', 'Komisi dinamis', 'Biaya layanan cashback bonus',
               'Biaya pemrosesan pesanan', 'Biaya afiliasi', 'Biaya iklan', 'Biaya layanan']
            : ['Biaya Administrasi', 'Biaya Layanan', 'Biaya Proses Pesanan', 'Premi',
               'Biaya Isi Saldo Otomatis (dari Penghasilan)', 'Biaya Komisi AMS', 'Biaya Transaksi'];

        DB::beginTransaction();
        try {
            // Pindai SEMUA sheet & deteksi baris header dinamis.
            // (File Income Shopee punya preamble + data per-pesanan di sheet "Income" baris 4.)
            $firstRowSeen = null;
            foreach ($reader->getSheetIterator() as $sheet) {
                $colMap = null; // header sheet ini belum ketemu

                foreach ($sheet->getRowIterator() as $row) {
                    $cells = $row->toArray();
                    if ($firstRowSeen === null) $firstRowSeen = $cells;

                    if ($colMap === null) {
                        // Coba kenali baris ini sebagai header (punya kolom order + kolom nilai)
                        $tmp = [];
                        foreach ($cells as $idx => $name) {
                            $name = trim((string) $name);
                            if ($name !== '') $tmp[$name] = $idx;
                        }
                        if ($platform === 'TikTok') {
                            $hasOrder = isset($tmp['Order ID']) || isset($tmp['Order id']) || isset($tmp['ID Pesanan/Penyesuaian']);
                            $hasNet = isset($tmp['Settlement Amount']) || isset($tmp['Settlement amount']) || isset($tmp['Jumlah penyelesaian pembayaran']);
                        } else { // Shopee
                            $hasOrder = isset($tmp['No. Pesanan']);
                            $hasNet = isset($tmp['Total Penghasilan']);
                        }
                        if ($hasOrder && $hasNet) {
                            $colMap = $tmp;
                            $headersFound = $cells;
                        }
                        continue;
                    }

                    // Baris data
                    if ($platform === 'TikTok') {
                        $orderId = isset($colMap['Order ID']) ? trim((string)($cells[$colMap['Order ID']] ?? '')) : null;
                        if (empty($orderId) && isset($colMap['Order id'])) {
                            $orderId = trim((string)($cells[$colMap['Order id']] ?? ''));
                        }
                        if (empty($orderId) && isset($colMap['ID Pesanan/Penyesuaian'])) {
                            $orderId = trim((string)($cells[$colMap['ID Pesanan/Penyesuaian']] ?? ''));
                        }

                        $netIncome = isset($colMap['Settlement Amount']) ? $this->parseNumber($cells[$colMap['Settlement Amount']] ?? 0) : 0;
                        if ($netIncome == 0 && isset($colMap['Settlement amount'])) {
                            $netIncome = $this->parseNumber($cells[$colMap['Settlement amount']] ?? 0);
                        }
                        if ($netIncome == 0 && isset($colMap['Jumlah penyelesaian pembayaran'])) {
                            $netIncome = $this->parseNumber($cells[$colMap['Jumlah penyelesaian pembayaran']] ?? 0);
                        }
                    } else { // Shopee
                        $orderId = isset($colMap['No. Pesanan']) ? trim((string)($cells[$colMap['No. Pesanan']] ?? '')) : null;
                        $netIncome = isset($colMap['Total Penghasilan']) ? $this->parseNumber($cells[$colMap['Total Penghasilan']] ?? 0) : 0;
                    }

                    if (empty($orderId)) continue;

                    // F1: tanggal pengakuan = tgl rilis dana dari FILE (Shopee: "Tanggal Dana Dilepaskan";
                    // TikTok: "Waktu pembayaran pesanan" sbg proxy, krn tak ada kolom tgl-rilis). Fallback now() di bawah.
                    if (!isset($dateByOrder[$orderId])) {
                        if ($platform === 'TikTok') {
                            $rawTgl = isset($colMap['Waktu pembayaran pesanan']) ? ($cells[$colMap['Waktu pembayaran pesanan']] ?? '')
                                    : (isset($colMap['Waktu pemesanan']) ? ($cells[$colMap['Waktu pemesanan']] ?? '') : '');
                        } else {
                            $rawTgl = isset($colMap['Tanggal Dana Dilepaskan']) ? ($cells[$colMap['Tanggal Dana Dilepaskan']] ?? '') : '';
                        }
                        $parsed = $this->parseDate($rawTgl);
                        if ($parsed) $dateByOrder[$orderId] = $parsed;
                    }

                    // Jumlahkan semua baris untuk order yang sama (potongan iklan/LIVE bisa baris terpisah)
                    $netByOrder[$orderId] = ($netByOrder[$orderId] ?? 0) + $netIncome;

                    // ADITIF — PENANDA AFILIASI (TikTok): baca kolom "Komisi Afiliasi" saja.
                    // Tidak menyentuh net/gross/fee/margin/HPP. Hanya untuk tahu pesanan ini afiliasi.
                    if ($platform === 'TikTok' && isset($colMap['Komisi Afiliasi'])) {
                        $va = $this->parseNumber($cells[$colMap['Komisi Afiliasi']] ?? 0);
                        if ($va != 0) $afiliasiByOrder[$orderId] = ($afiliasiByOrder[$orderId] ?? 0) + $va;
                    }

                    // Omset/subtotal otoritatif dari kolom yang sudah dipetakan per platform
                    $grossRow = 0;
                    foreach ($grossCols as $gc) {
                        if (isset($colMap[$gc])) $grossRow += $this->parseNumber($cells[$colMap[$gc]] ?? 0);
                    }
                    if ($grossRow != 0) {
                        $grossByOrder[$orderId] = round(($grossByOrder[$orderId] ?? 0) + $grossRow, 2);
                    }

                    // Rincian biaya: HANYA kolom top-level dari allowlist (hindari induk/anak/duplikat)
                    foreach ($feeCols as $name) {
                        if (!isset($colMap[$name])) continue;
                        $raw = $cells[$colMap[$name]] ?? '';
                        if (trim((string) $raw) === '') continue;
                        $val = $this->parseNumber($raw);
                        if ($val != 0) {
                            $feeByOrder[$orderId][$name] = round(($feeByOrder[$orderId][$name] ?? 0) + $val, 2);
                        }
                    }
                }
            }
            if (empty($headersFound) && $firstRowSeen) $headersFound = $firstRowSeen;

            $countSettled = 0;
            foreach ($netByOrder as $orderId => $net) {
                $header = PenjualanHeader::where('external_order_id', $orderId)->first();
                if (!$header) continue;

                // Jangan settle pesanan yang sudah DIBATALKAN — cegah kas & pendapatan hantu.
                if ($header->status_pesanan === 'Batal') continue;

                // Omset otoritatif: dari settlement (Shopee) atau fallback GMV impor (TikTok = subtotal after discount)
                $gross = $grossByOrder[$orderId] ?? 0;
                if ($gross <= 0) {
                    $gross = (float) $header->gmv_kotor;
                }
                $potonganTotal = round($gross - $net, 2); // potongan riil = omset - net (otoritatif)

                // Rincian biaya hanya disimpan jika REKONSILIASI dgn total (mencegah salah-petakan
                // file berjenjang spt TikTok yang bisa menggandakan komisi).
                $fees = $feeByOrder[$orderId] ?? [];
                $sumFees = round(-array_sum($fees), 2); // jadikan positif (biaya umumnya negatif)
                $rincianValid = !empty($fees) && abs($sumFees - $potonganTotal) <= 100; // toleransi pembulatan Rp100

                // Akun saldo marketplace (sesuai master akun kas)
                $akunMp = $platform === 'TikTok' ? 'Saldo TikTok Shop' : 'Saldo Shopee Seller';

                $tglCair = $dateByOrder[$orderId] ?? now()->toDateString(); // F1: tgl rilis dari file

                $header->net_settlement = $net;
                $header->gross_settlement = round($gross, 2);
                $header->potongan_detail = $rincianValid ? $fees : null;
                $header->status_pembayaran = 'Cair';
                $header->tgl_cair_saldo = $tglCair;
                $header->akun_masuk = $akunMp;
                // ADITIF: penanda afiliasi (komisi afiliasi, positif). Tidak memengaruhi net/margin/HPP/kas.
                $header->komisi_afiliasi = round(abs($afiliasiByOrder[$orderId] ?? 0), 2);
                $header->save();
                $countSettled++;

                // Uang MASUK ke saldo marketplace, direkonsiliasi ke net TERBARU. Bila re-upload
                // membawa net berbeda (koreksi), catat SELISIHnya saja → total kas = net (tak dobel,
                // tak tertinggal di nilai lama). Re-upload net sama → selisih 0 → tak ada baris baru.
                $existing = (float) (\App\Models\MutasiKas::where('ref_id', $header->internal_id)
                    ->where('kategori', 'penjualan')
                    ->selectRaw("SUM(CASE WHEN tipe='masuk' THEN jumlah ELSE -jumlah END) s")->value('s') ?? 0);
                $delta = round($net - $existing, 2);
                if (abs($delta) >= 1 && ($net > 0 || $existing > 0)) {
                    \App\Models\MutasiKas::catat($akunMp, $delta > 0 ? 'masuk' : 'keluar', abs($delta), 'penjualan', $header->internal_id, 'Settlement ' . $platform . ' ' . $orderId . ($existing != 0 ? ' (koreksi)' : ''), null, $tglCair);
                }

                $this->alokasiMarginFinal($header, $net);
            }

            DB::commit();

            if ($countSettled == 0) {
                $sampleHeaders = array_slice($headersFound, 0, 15);
                return redirect()->back()->with('error', "Gagal mencocokkan. 0 pesanan cair. Pastikan Anda mengupload file yang benar. Kolom di file Anda: " . implode(', ', $sampleHeaders));
            }

            return redirect()->back()->with('success', "Berhasil mencocokkan $countSettled pesanan menjadi CAIR (Settled).");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memproses settlement: ' . $e->getMessage());
        }
    }

    /**
     * Fix #4 — Margin FINAL per produk (spec 7.2/7.3):
     * net settlement dialokasikan PROPORSIONAL ke tiap produk berdasarkan subtotal,
     * lalu margin_satuan = (net per produk / qty) − hpp_satuan.
     * hpp_satuan sudah mencakup Lapis 1 + Lapis 2 (dihitung saat racik).
     */
    private function alokasiMarginFinal(PenjualanHeader $header, float $net): void
    {
        $details = PenjualanDetail::where('internal_id', $header->internal_id)->get();
        if ($details->isEmpty()) return;

        // Basis alokasi = subtotal tiap baris (harga_satuan × qty)
        $sumSub = 0.0;
        foreach ($details as $d) {
            $sumSub += ((float) $d->harga_satuan) * (int) $d->qty;
        }

        $count = $details->count();
        foreach ($details as $d) {
            $qty = max(1, (int) $d->qty);
            $lineSub = ((float) $d->harga_satuan) * $qty;
            // Bila subtotal nol (mis. harga belum terisi), bagi rata antar baris
            $share = $sumSub > 0 ? ($lineSub / $sumSub) : (1 / $count);
            $netLine = $net * $share;

            if (is_null($d->subtotal) || (float) $d->subtotal == 0.0) {
                $d->subtotal = $lineSub; // isi subtotal jika belum ada (impor marketplace)
            }

            // Hanya hitung margin final bila HPP sudah ada (sudah diracik)
            if (!is_null($d->hpp_satuan)) {
                $netPerUnit = $netLine / $qty;
                $d->margin_satuan = round($netPerUnit - (float) $d->hpp_satuan, 2);
            }
            $d->save();
        }
    }
}
