<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Hagos ERP - Tambah Produk</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">

<div class="min-h-screen p-6 max-w-4xl mx-auto">
    <header class="mb-6">
        <a href="{{ route('master_produk.index') }}" class="text-sm text-gray-500 hover:underline">← Kembali ke Master Produk</a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Tambah Produk Baru</h1>
        <p class="text-gray-500 text-sm">Satu aroma bisa langsung beberapa ukuran. Semua disimpan sekaligus.</p>
    </header>

    @if($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded">
            <ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @if(session('error'))<div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded">{{ session('error') }}</div>@endif

    {{-- Stepper --}}
    <div class="flex items-center mb-6">
        @foreach(['Identitas','Varian & Resep','Harga Jual','Review'] as $i => $label)
            <div class="flex items-center {{ $i < 3 ? 'flex-1' : '' }}">
                <div id="step-dot-{{ $i+1 }}" class="flex items-center gap-2">
                    <span class="step-num w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold {{ $i === 0 ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500' }}">{{ $i+1 }}</span>
                    <span class="step-lbl text-sm font-medium hidden sm:inline {{ $i === 0 ? 'text-indigo-700' : 'text-gray-400' }}">{{ $label }}</span>
                </div>
                @if($i < 3)<div class="flex-1 h-0.5 bg-gray-200 mx-2"></div>@endif
            </div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('master_produk.store') }}" id="produk-form">
        @csrf

        {{-- ───────── STEP 1: IDENTITAS ───────── --}}
        <div class="step-panel" data-step="1">
            <div class="bg-white rounded-xl shadow-sm p-6 space-y-5">
                <h2 class="font-bold text-gray-800">1. Identitas Aroma</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Aroma / Bibit <span class="text-red-500">*</span></label>
                    <div class="flex gap-4 mb-3">
                        <label class="flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" name="aroma_mode" value="existing" checked onchange="toggleAroma()"> Pilih yang ada</label>
                        <label class="flex items-center gap-1.5 text-sm cursor-pointer"><input type="radio" name="aroma_mode" value="baru" onchange="toggleAroma()"> + Aroma Baru</label>
                    </div>

                    <div id="aroma-existing">
                        <select name="bibit_id" id="bibit_id" onchange="onAromaChange()"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- Pilih Aroma --</option>
                            @foreach($bibits as $b)
                                <option value="{{ $b->bibit_id }}" data-aroma="{{ $b->sku_aroma }}" data-nama="{{ $b->nama_bibit }}" data-harga="{{ $b->harga_per_ml }}">
                                    {{ $b->sku_aroma }} · {{ $b->nama_bibit }}{{ $b->merek_bibit ? ' ('.$b->merek_bibit.')' : '' }} — Rp {{ number_format((float)$b->harga_per_ml,0,',','.') }}/ml
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="aroma-baru" class="hidden border border-indigo-200 bg-indigo-50 rounded-lg p-4 space-y-3">
                        <p class="text-xs text-indigo-700 font-semibold">Bibit baru akan dibuat (ID otomatis: {{ $nextBibitId }})</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Kode Aroma (SKU Aroma) *</label>
                                <input type="text" name="b_sku_aroma" id="b_sku_aroma" placeholder="cth: HGS058" oninput="onAromaChange()" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Nama Bibit (versi kita) *</label>
                                <input type="text" name="b_nama_bibit" id="b_nama_bibit" placeholder="cth: Boshell" oninput="onAromaChange()" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Merek Bibit</label>
                                <input type="text" name="b_merek" placeholder="cth: Luzi" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Nama Asli (di merek tsb)</label>
                                <input type="text" name="b_nama_asli" placeholder="cth: viabomsel" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Harga per ml (Rp) *</label>
                                <input type="number" name="b_harga_per_ml" id="b_harga_per_ml" step="0.01" min="0" oninput="onAromaChange()" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Stok Awal (ml)</label>
                                    <input type="number" name="b_stok_ml" step="0.01" min="0" value="0" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Threshold</label>
                                    <input type="number" name="b_threshold_ml" step="0.01" min="0" value="0" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bentuk</label>
                        <select name="bentuk" id="bentuk" onchange="onBentukChange()" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach($bentukList as $bt)<option value="{{ $bt }}" {{ $bt === 'REG' ? 'selected' : '' }}>{{ $bt }}</option>@endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Dipakai untuk semua varian & pola SKU ID.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                        <input type="text" name="kategori" list="kategori-list" placeholder="cth: Uniseks" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <datalist id="kategori-list">@foreach($kategoriList as $kt)<option value="{{ $kt }}">@endforeach</datalist>
                    </div>
                </div>
            </div>
        </div>

        {{-- ───────── STEP 2: VARIAN & RESEP ───────── --}}
        <div class="step-panel hidden" data-step="2">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="font-bold text-gray-800">2. Varian Ukuran & Resep</h2>
                    <button type="button" onclick="addVariant()" class="text-sm bg-indigo-600 text-white px-3 py-1.5 rounded-md hover:bg-indigo-700">+ Tambah Ukuran</button>
                </div>
                <div id="variant-container" class="space-y-4"></div>
            </div>
        </div>

        {{-- ───────── STEP 3: HARGA ───────── --}}
        <div class="step-panel hidden" data-step="3">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="font-bold text-gray-800 mb-1">3. Harga Jual per Channel</h2>
                <p class="text-xs text-gray-400 mb-4">Isi channel yang dijual saja. Margin = harga − HPP channel tsb. Minimal 1 channel per varian.</p>
                <div id="harga-container" class="space-y-6"></div>
            </div>
        </div>

        {{-- ───────── STEP 4: REVIEW ───────── --}}
        <div class="step-panel hidden" data-step="4">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="font-bold text-gray-800 mb-4">4. Review & Simpan</h2>
                <div id="review-content" class="space-y-4 text-sm"></div>
            </div>
        </div>

        <div class="flex justify-between mt-6">
            <button type="button" id="btn-prev" onclick="prevStep()" class="px-5 py-2 bg-gray-200 text-gray-700 rounded-md font-semibold hidden hover:bg-gray-300">← Kembali</button>
            <div class="ml-auto flex gap-2">
                <button type="button" id="btn-next" onclick="nextStep()" class="px-6 py-2 bg-indigo-600 text-white rounded-md font-semibold hover:bg-indigo-700">Lanjut →</button>
                <button type="submit" id="btn-submit" class="px-6 py-2 bg-emerald-600 text-white rounded-md font-semibold hover:bg-emerald-700 hidden">✓ Simpan Produk</button>
            </div>
        </div>
    </form>
</div>
</div>

{{-- Template varian (step 2) --}}
<template id="variant-template">
    <div class="variant-block border border-gray-200 rounded-lg p-4" data-idx="__IDX__">
        <div class="flex justify-between items-center mb-3">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">Ukuran (ml)</label>
                <input type="number" name="varian[__IDX__][ukuran]" class="v-ukuran w-24 border border-gray-300 rounded-md px-2 py-1.5 text-sm" min="1" step="1" oninput="onVariantInput(__IDX__)">
                <span class="v-sku text-xs text-gray-400"></span>
            </div>
            <button type="button" onclick="removeVariant(__IDX__)" class="text-red-500 text-sm hover:text-red-700">✕ Hapus</button>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Nama Produk *</label>
                <input type="text" name="varian[__IDX__][nama_produk]" class="v-nama w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Konsentrasi</label>
                <input type="text" name="varian[__IDX__][konsentrasi]" placeholder="20%" class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Jml Tester *</label>
                <input type="number" name="varian[__IDX__][jml_tester]" value="0" step="0.01" min="0" class="v-tester w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" oninput="onVariantInput(__IDX__)">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">ml Bibit Utama *</label>
                <input type="number" name="varian[__IDX__][ml_bibit_utama]" step="0.01" min="0" class="v-bibit w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" oninput="onVariantInput(__IDX__)">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">ml Absolute *</label>
                <input type="number" name="varian[__IDX__][ml_absolute]" step="0.01" min="0" class="v-abs w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" oninput="onVariantInput(__IDX__)">
            </div>
        </div>
        <div class="bg-gray-50 rounded-md p-2 flex justify-between items-center text-sm">
            <span class="text-xs text-gray-500">HPP per botol (basis Reguler):</span>
            <span class="v-hpp font-bold text-orange-600">—</span>
        </div>
    </div>
</template>

<script>
const HPP_URL = '{{ route("master_produk.hpp_preview") }}';
const CSRF = document.querySelector('meta[name=csrf-token]').content;
const CHANNELS = @json($channels);
let currentStep = 1, totalSteps = 4, variantSeq = 0;
const hppByIdx = {}; // idx => {per_channel, ref}

// ── Aroma ──
function toggleAroma() {
    const mode = document.querySelector('input[name=aroma_mode]:checked').value;
    document.getElementById('aroma-existing').classList.toggle('hidden', mode !== 'existing');
    document.getElementById('aroma-baru').classList.toggle('hidden', mode !== 'baru');
    onAromaChange();
}
function getAroma() {
    const mode = document.querySelector('input[name=aroma_mode]:checked').value;
    if (mode === 'existing') {
        const sel = document.getElementById('bibit_id'); const opt = sel.options[sel.selectedIndex];
        if (!opt || !opt.value) return null;
        return { aroma: opt.dataset.aroma, nama: opt.dataset.nama, harga: parseFloat(opt.dataset.harga) || 0 };
    }
    return { aroma: document.getElementById('b_sku_aroma').value, nama: document.getElementById('b_nama_bibit').value, harga: parseFloat(document.getElementById('b_harga_per_ml').value) || 0 };
}
function getBentuk() { return document.getElementById('bentuk').value || 'REG'; }
function computeSku(ukuran) { const a = getAroma(); return (a && a.aroma && ukuran) ? `${a.aroma}-${ukuran}-${getBentuk()}` : ''; }
function onAromaChange() { document.querySelectorAll('.variant-block').forEach(b => refreshVariantNamaSku(+b.dataset.idx)); }
function onBentukChange() { onAromaChange(); }

// ── Varian ──
function addVariant() {
    const idx = variantSeq++;
    const html = document.getElementById('variant-template').innerHTML.replaceAll('__IDX__', idx);
    const wrap = document.createElement('div'); wrap.innerHTML = html.trim();
    document.getElementById('variant-container').appendChild(wrap.firstChild);
}
function removeVariant(idx) {
    const el = document.querySelector(`.variant-block[data-idx="${idx}"]`);
    if (el) el.remove();
    delete hppByIdx[idx];
    if (document.querySelectorAll('.variant-block').length === 0) addVariant();
}
function block(idx) { return document.querySelector(`.variant-block[data-idx="${idx}"]`); }
function refreshVariantNamaSku(idx) {
    const b = block(idx); if (!b) return;
    const ukuran = b.querySelector('.v-ukuran').value;
    const a = getAroma();
    const namaEl = b.querySelector('.v-nama');
    if (a && a.nama && !namaEl.dataset.touched) namaEl.value = a.nama + (ukuran ? ' ' + ukuran + 'ml' : '');
    b.querySelector('.v-sku').textContent = computeSku(ukuran) ? '→ ' + computeSku(ukuran) : '';
}
let vTimer = null;
function onVariantInput(idx) {
    const b = block(idx); if (b) { const namaEl=b.querySelector('.v-nama'); if(document.activeElement===namaEl) namaEl.dataset.touched='1'; }
    refreshVariantNamaSku(idx);
    clearTimeout(vTimer); vTimer = setTimeout(() => refreshVariantHpp(idx), 350);
}
async function refreshVariantHpp(idx) {
    const b = block(idx); if (!b) return;
    const a = getAroma();
    const payload = {
        harga_per_ml: a ? a.harga : 0, nama_bibit: a ? a.nama : '-',
        ukuran_ml: b.querySelector('.v-ukuran').value || 0,
        ml_bibit_utama: b.querySelector('.v-bibit').value || 0,
        ml_absolute: b.querySelector('.v-abs').value || 0,
        jml_tester: b.querySelector('.v-tester').value || 0,
    };
    try {
        const res = await fetch(HPP_URL, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body: JSON.stringify(payload) });
        const data = await res.json();
        hppByIdx[idx] = data;
        b.querySelector('.v-hpp').textContent = rupiah(data.ref.hpp_per_unit);
    } catch(e) { console.error(e); }
}
function rupiah(n){ return 'Rp ' + Number(n||0).toLocaleString('id-ID'); }

// ── Step 3: harga ──
function buildHargaStep() {
    const cont = document.getElementById('harga-container'); cont.innerHTML = '';
    document.querySelectorAll('.variant-block').forEach(b => {
        const idx = +b.dataset.idx;
        const ukuran = b.querySelector('.v-ukuran').value;
        const sku = computeSku(ukuran) || '(ukuran belum diisi)';
        const nama = b.querySelector('.v-nama').value || '';
        const hpp = hppByIdx[idx] ? hppByIdx[idx].per_channel : {};
        let rows = '';
        CHANNELS.forEach(ch => {
            const h = hpp[ch];
            rows += `<div class="flex flex-wrap items-center gap-3 py-1.5 border-b border-gray-100">
                <span class="w-40 text-sm text-gray-700">${ch}</span>
                <span class="text-sm text-gray-400">Rp</span>
                <input type="number" name="varian[${idx}][harga][${ch}]" data-idx="${idx}" data-ch="${ch}" min="0" step="1" oninput="updateMargin(this)" class="harga-input w-32 border border-gray-300 rounded-md px-2 py-1 text-sm">
                <span class="text-xs text-gray-400">HPP: ${h!=null?rupiah(h):'—'}</span>
                <span class="margin-out text-xs font-semibold" data-idx="${idx}" data-ch="${ch}"></span>
            </div>`;
        });
        cont.insertAdjacentHTML('beforeend', `<div class="border border-gray-200 rounded-lg p-4">
            <p class="font-semibold text-gray-800 mb-2">${sku} <span class="font-normal text-gray-400">· ${nama}</span></p>${rows}</div>`);
    });
}
function updateMargin(input) {
    const idx = input.dataset.idx, ch = input.dataset.ch;
    const harga = parseFloat(input.value) || 0;
    const hpp = hppByIdx[idx] ? hppByIdx[idx].per_channel[ch] : null;
    const out = document.querySelector(`.margin-out[data-idx="${idx}"][data-ch="${CSS.escape(ch)}"]`);
    if (!out) return;
    if (!harga || hpp == null) { out.textContent=''; return; }
    const m = harga - hpp;
    out.textContent = (m>=0?'+':'') + rupiah(m) + ' margin';
    out.className = 'margin-out text-xs font-semibold ' + (m>=0?'text-emerald-600':'text-red-600');
}

// ── Steps ──
function showStep(n) {
    document.querySelectorAll('.step-panel').forEach(p => p.classList.toggle('hidden', +p.dataset.step !== n));
    for (let i=1;i<=totalSteps;i++){
        const dot=document.getElementById('step-dot-'+i); const num=dot.querySelector('.step-num'); const lbl=dot.querySelector('.step-lbl'); const on=i<=n;
        num.className='step-num w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold '+(on?'bg-indigo-600 text-white':'bg-gray-200 text-gray-500');
        if(lbl) lbl.className='step-lbl text-sm font-medium hidden sm:inline '+(on?'text-indigo-700':'text-gray-400');
    }
    document.getElementById('btn-prev').classList.toggle('hidden', n===1);
    document.getElementById('btn-next').classList.toggle('hidden', n===totalSteps);
    document.getElementById('btn-submit').classList.toggle('hidden', n!==totalSteps);
    currentStep = n;
    if (n===3) buildHargaStep();
    if (n===4) renderReview();
}
function validateStep(n) {
    if (n===1) {
        const mode=document.querySelector('input[name=aroma_mode]:checked').value;
        if (mode==='existing' && !document.getElementById('bibit_id').value) return msg('Pilih aroma dulu.');
        if (mode==='baru' && (!document.getElementById('b_sku_aroma').value||!document.getElementById('b_nama_bibit').value||!document.getElementById('b_harga_per_ml').value)) return msg('Lengkapi aroma baru (kode, nama, harga/ml).');
    }
    if (n===2) {
        const blocks=document.querySelectorAll('.variant-block');
        if (blocks.length===0) return msg('Tambah minimal 1 ukuran.');
        for (const b of blocks) {
            if (!b.querySelector('.v-ukuran').value) return msg('Isi ukuran tiap varian.');
            if (!b.querySelector('.v-nama').value.trim()) return msg('Isi nama produk tiap varian.');
            if (b.querySelector('.v-bibit').value===''||b.querySelector('.v-abs').value===''||b.querySelector('.v-tester').value==='') return msg('Lengkapi resep (ml bibit, absolute, tester) tiap varian.');
        }
        // cek ukuran duplikat
        const uks = Array.from(blocks).map(b=>b.querySelector('.v-ukuran').value);
        if (new Set(uks).size !== uks.length) return msg('Ada ukuran yang sama. Tiap varian harus beda ukuran.');
    }
    if (n===3) {
        const adaTanpaHarga = Array.from(document.querySelectorAll('#harga-container > div')).some(blk => {
            return !Array.from(blk.querySelectorAll('.harga-input')).some(i=>parseFloat(i.value)>0);
        });
        if (adaTanpaHarga) return msg('Tiap varian harus punya harga minimal 1 channel.');
    }
    return true;
}
function msg(m){ alert(m); return false; }
function nextStep(){ if(validateStep(currentStep)===true) showStep(Math.min(currentStep+1,totalSteps)); }
function prevStep(){ showStep(Math.max(currentStep-1,1)); }

function renderReview() {
    const a = getAroma()||{};
    let html = `<div class="grid grid-cols-2 gap-3">
        <div><p class="text-xs text-gray-400">Aroma</p><p class="font-semibold">${a.aroma||'-'} · ${a.nama||'-'}</p></div>
        <div><p class="text-xs text-gray-400">Bentuk / Kategori</p><p class="font-semibold">${getBentuk()} · ${document.querySelector('[name=kategori]').value||'-'}</p></div>
    </div>`;
    document.querySelectorAll('.variant-block').forEach(b => {
        const idx=+b.dataset.idx; const ukuran=b.querySelector('.v-ukuran').value;
        const sku=computeSku(ukuran); const nama=b.querySelector('.v-nama').value;
        const hppRef = hppByIdx[idx] ? rupiah(hppByIdx[idx].ref.hpp_per_unit) : '-';
        let hrows='';
        CHANNELS.forEach(ch=>{
            const inp=document.querySelector(`.harga-input[data-idx="${idx}"][data-ch="${CSS.escape(ch)}"]`);
            const h=inp?parseFloat(inp.value)||0:0;
            if(h>0){ const hpp=hppByIdx[idx]?hppByIdx[idx].per_channel[ch]:null; const m=hpp!=null?h-hpp:null;
                hrows+=`<tr class="border-t border-gray-100"><td class="py-0.5">${ch}</td><td class="py-0.5 text-right">${rupiah(h)}</td><td class="py-0.5 text-right ${m>=0?'text-emerald-600':'text-red-600'}">${m!=null?(m>=0?'+':'')+rupiah(m):'-'}</td></tr>`;
            }
        });
        html += `<div class="border border-gray-200 rounded-lg p-3 mt-3">
            <p class="font-semibold text-gray-800">${sku} <span class="font-normal text-gray-400">· ${nama} · ${ukuran}ml · HPP ${hppRef}</span></p>
            <table class="w-full text-sm mt-2"><thead><tr class="text-xs text-gray-400"><th class="text-left">Channel</th><th class="text-right">Harga</th><th class="text-right">Margin</th></tr></thead><tbody>${hrows||'<tr><td class="text-red-500">Belum ada harga!</td></tr>'}</tbody></table>
        </div>`;
    });
    document.getElementById('review-content').innerHTML = html;
}

// init
addVariant();
showStep(1);
</script>
</body>
</html>
