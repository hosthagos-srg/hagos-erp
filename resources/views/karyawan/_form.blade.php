@php $k = $k ?? null; @endphp
<div class="grid grid-cols-2 gap-3">
    <div class="col-span-2">
        <label class="block text-xs font-medium text-gray-600 mb-1">Nama <span class="text-red-500">*</span></label>
        <input type="text" name="nama" value="{{ old('nama', $k->nama ?? '') }}" required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Posisi / Jabatan</label>
        <input type="text" name="posisi" value="{{ old('posisi', $k->posisi ?? '') }}" placeholder="cth: Packing" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Gaji Pokok (Rp)</label>
        <input type="number" name="gaji_pokok" value="{{ old('gaji_pokok', $k->gaji_pokok ?? 0) }}" min="0" step="1" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">No HP</label>
        <input type="text" name="no_hp" value="{{ old('no_hp', $k->no_hp ?? '') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Tgl Masuk</label>
        <input type="date" name="tgl_masuk" value="{{ old('tgl_masuk', optional($k->tgl_masuk ?? null)->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
        <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
            <option value="Aktif" {{ ($k->status ?? 'Aktif') === 'Aktif' ? 'selected' : '' }}>Aktif</option>
            <option value="Nonaktif" {{ ($k->status ?? '') === 'Nonaktif' ? 'selected' : '' }}>Nonaktif</option>
        </select>
    </div>
    <div class="col-span-2">
        <label class="block text-xs font-medium text-gray-600 mb-1">Catatan</label>
        <textarea name="catatan" rows="2" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">{{ old('catatan', $k->catatan ?? '') }}</textarea>
    </div>
</div>
