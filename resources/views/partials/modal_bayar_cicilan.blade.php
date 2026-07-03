{{-- Modal Bayar Cicilan --}}
@php
    $akunsBayar = \App\Models\MasterAkunKas::whereNotIn('tipe', ['Piutang', 'Saldo MP'])->orderBy('akun_id')->pluck('nama_akun');
@endphp
<div id="modal-bayar" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-1">Catat Pembayaran Cicilan</h2>
        <p class="text-sm text-gray-500 mb-1">Periode: <span id="modal-periode" class="font-semibold text-gray-800"></span></p>
        <p class="text-sm text-gray-500 mb-4">Tagihan pokok: <span id="modal-tagihan" class="font-semibold text-red-700"></span></p>

        <form id="form-bayar" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Dibayar (pokok cicilan)</label>
                <input type="number" name="jumlah_bayar" id="input-jumlah-bayar" step="1" min="1"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Biaya Tambahan
                    <span class="text-gray-400 font-normal">(notifikasi, tahunan, dll — opsional)</span>
                </label>
                <div class="flex gap-2">
                    <input type="number" name="biaya_tambahan" id="input-biaya-tambahan" step="1" min="0" placeholder="0"
                        class="w-1/2 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                    <input type="text" name="keterangan_biaya" placeholder="Keterangan (opsional)"
                        class="w-1/2 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Total Dibayar</label>
                <p id="label-total" class="text-lg font-bold text-gray-900">—</p>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Dibayar dari Akun</label>
                <select name="akun" required
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="">— pilih akun kas —</option>
                    @foreach($akunsBayar as $a)
                        <option value="{{ $a }}">{{ $a }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Bayar</label>
                <input type="date" name="tgl_bayar" value="{{ date('Y-m-d') }}"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-emerald-600 text-white py-2 rounded-md font-semibold hover:bg-emerald-700">Simpan</button>
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-200 text-gray-700 py-2 rounded-md font-semibold hover:bg-gray-300">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function openBayarModal(id, periode, tagihan) {
    document.getElementById('modal-periode').textContent = periode;
    document.getElementById('modal-tagihan').textContent = 'Rp ' + Number(tagihan).toLocaleString('id-ID');
    document.getElementById('input-jumlah-bayar').value = tagihan;
    document.getElementById('input-biaya-tambahan').value = '';
    document.getElementById('form-bayar').action = '/utang/cicilan/' + id + '/bayar';
    document.getElementById('modal-bayar').classList.remove('hidden');
    hitungTotal();
}
function closeModal() {
    document.getElementById('modal-bayar').classList.add('hidden');
}
function hitungTotal() {
    const pokok = parseFloat(document.getElementById('input-jumlah-bayar').value) || 0;
    const tambahan = parseFloat(document.getElementById('input-biaya-tambahan').value) || 0;
    const total = pokok + tambahan;
    document.getElementById('label-total').textContent = 'Rp ' + total.toLocaleString('id-ID');
}
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('input-jumlah-bayar').addEventListener('input', hitungTotal);
    document.getElementById('input-biaya-tambahan').addEventListener('input', hitungTotal);
});
</script>
