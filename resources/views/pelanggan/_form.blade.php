@php $p = $p ?? null; @endphp
<div class="grid grid-cols-2 gap-3">
    <div class="col-span-2">
        <label class="block text-xs font-medium text-gray-600 mb-1">Nama <span class="text-red-500">*</span></label>
        <input type="text" name="nama" value="{{ old('nama', $p->nama ?? '') }}" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
        <p class="text-xs text-gray-400 mt-0.5">Harus sama persis dgn nama pembeli di pesanan agar riwayat tercocok.</p>
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Tipe</label>
        <input type="text" name="tipe" value="{{ old('tipe', $p->tipe ?? '') }}" list="tipe-pelanggan-list" placeholder="cth: Reseller A" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
        <datalist id="tipe-pelanggan-list">@foreach($tipeList as $t)<option value="{{ $t }}">@endforeach</datalist>
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">No HP</label>
        <input type="text" name="no_hp" value="{{ old('no_hp', $p->no_hp ?? '') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Kota</label>
        <input type="text" name="kota" value="{{ old('kota', $p->kota ?? '') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
        <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            <option value="Aktif" {{ ($p->status ?? 'Aktif') === 'Aktif' ? 'selected' : '' }}>Aktif</option>
            <option value="Nonaktif" {{ ($p->status ?? '') === 'Nonaktif' ? 'selected' : '' }}>Nonaktif</option>
        </select>
    </div>
    <div class="col-span-2">
        <label class="block text-xs font-medium text-gray-600 mb-1">Alamat</label>
        <input type="text" name="alamat" value="{{ old('alamat', $p->alamat ?? '') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
    </div>
    <div class="col-span-2">
        <label class="block text-xs font-medium text-gray-600 mb-1">Catatan</label>
        <textarea name="catatan" rows="2" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">{{ old('catatan', $p->catatan ?? '') }}</textarea>
    </div>
</div>
