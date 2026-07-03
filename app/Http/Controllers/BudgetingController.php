<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\AnggaranMingguan;
use App\Models\MasterKategori;
use App\Services\BudgetingService;

class BudgetingController extends Controller
{
    public function __construct(private BudgetingService $budgeting) {}

    /** Kategori pengeluaran yang boleh dianggarkan (kecuali belanja bibit/komponen). */
    private function kategoriList()
    {
        return MasterKategori::where('tipe_kategori', 'Kategori Pengeluaran')
            ->whereNotIn('nilai', ['Belanja Bibit', 'Belanja Komponen'])
            ->orderBy('id')->pluck('nilai');
    }

    public function index(Request $request)
    {
        // Bulan yang dilihat (default: bulan ini)
        $bulanInput = $request->input('bulan'); // format YYYY-MM
        try {
            $monthStart = $bulanInput
                ? Carbon::createFromFormat('Y-m', $bulanInput)->startOfMonth()
                : Carbon::today()->startOfMonth();
        } catch (\Exception $e) {
            $monthStart = Carbon::today()->startOfMonth();
        }

        $bd = $this->budgeting->monthBreakdown($monthStart);

        $kategoriList = $this->kategoriList();
        $prevMonth = $monthStart->copy()->subMonth()->format('Y-m');
        $nextMonth = $monthStart->copy()->addMonth()->format('Y-m');

        return view('budgeting.index', array_merge($bd, compact(
            'kategoriList', 'prevMonth', 'nextMonth'
        )));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kategori'        => 'required|string',
            'jumlah_mingguan' => 'required|numeric|min:0',
            'catatan'         => 'nullable|string|max:255',
        ]);

        if (!$this->kategoriList()->contains($request->kategori)) {
            return back()->with('error', 'Kategori tidak valid.');
        }

        AnggaranMingguan::updateOrCreate(
            ['kategori' => $request->kategori],
            [
                'jumlah_mingguan' => (float) $request->jumlah_mingguan,
                'catatan'         => $request->catatan,
                'aktif'           => true,
            ]
        );

        return redirect()->route('budgeting.index')
            ->with('success', 'Anggaran ' . $request->kategori . ' disimpan: Rp ' . number_format((float) $request->jumlah_mingguan, 0, ',', '.') . ' / minggu.');
    }

    public function destroy(AnggaranMingguan $anggaran)
    {
        $nama = $anggaran->kategori;
        $anggaran->delete();
        return redirect()->route('budgeting.index')->with('success', 'Anggaran ' . $nama . ' dihapus.');
    }
}
