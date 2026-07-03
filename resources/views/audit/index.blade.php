<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas - Hagos ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">
    <div class="min-h-screen p-6">
        <header class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Log Aktivitas</h1>
            <p class="text-gray-600 mt-2">Jejak siapa membuat, mengubah, atau menghapus data finansial & stok.</p>
        </header>

        {{-- Filter --}}
        <form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-4 grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Aksi</label>
                <select name="action" class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm">
                    <option value="">Semua</option>
                    <option value="created" @selected(request('action')==='created')>Tambah</option>
                    <option value="updated" @selected(request('action')==='updated')>Ubah</option>
                    <option value="deleted" @selected(request('action')==='deleted')>Hapus</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">User</label>
                <select name="user_id" class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm">
                    <option value="">Semua</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected((string)request('user_id')===(string)$u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Modul</label>
                <select name="modul" class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm">
                    <option value="">Semua</option>
                    @foreach($moduls as $m)
                        <option value="{{ $m }}" @selected(request('modul')===$m)>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Dari</label>
                <input type="date" name="dari" value="{{ request('dari') }}" class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm">
            </div>
            <div class="flex gap-2">
                <div class="flex-1">
                    <label class="block text-xs text-gray-500 mb-1">Sampai</label>
                    <input type="date" name="sampai" value="{{ request('sampai') }}" class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm">
                </div>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 rounded-md h-[34px] self-end">Filter</button>
            </div>
        </form>

        @php
            $badge = [
                'created' => ['Tambah', 'bg-green-100 text-green-700'],
                'updated' => ['Ubah',   'bg-amber-100 text-amber-700'],
                'deleted' => ['Hapus',  'bg-red-100 text-red-700'],
            ];
            $fmt = function ($v) {
                if (is_null($v)) return '—';
                if (is_bool($v)) return $v ? 'true' : 'false';
                if (is_array($v)) return json_encode($v, JSON_UNESCAPED_UNICODE);
                return (string) $v;
            };
        @endphp

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="text-left px-4 py-3">Waktu</th>
                        <th class="text-left px-4 py-3">User</th>
                        <th class="text-left px-4 py-3">Aksi</th>
                        <th class="text-left px-4 py-3">Modul</th>
                        <th class="text-left px-4 py-3">Perubahan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                        @php [$label, $cls] = $badge[$log->action] ?? [$log->action, 'bg-gray-100 text-gray-700']; @endphp
                        <tr class="align-top">
                            <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">{{ $log->user_name ?? '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap"><span class="text-xs font-semibold px-2 py-0.5 rounded {{ $cls }}">{{ $label }}</span></td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="text-gray-800">{{ $log->model_label }}</span>
                                <span class="text-gray-400 text-xs">#{{ $log->auditable_id }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $old = $log->old_values ?? [];
                                    $new = $log->new_values ?? [];
                                    $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
                                @endphp
                                <details class="group">
                                    <summary class="cursor-pointer text-indigo-600 text-xs font-semibold list-none">
                                        {{ count($keys) }} field · lihat detail
                                    </summary>
                                    <div class="mt-2 space-y-1">
                                        @foreach($keys as $k)
                                            <div class="text-xs">
                                                <span class="font-semibold text-gray-700">{{ $k }}:</span>
                                                @if($log->action === 'updated')
                                                    <span class="text-red-600 line-through">{{ $fmt($old[$k] ?? null) }}</span>
                                                    <span class="text-gray-400">→</span>
                                                    <span class="text-green-700">{{ $fmt($new[$k] ?? null) }}</span>
                                                @elseif($log->action === 'created')
                                                    <span class="text-green-700">{{ $fmt($new[$k] ?? null) }}</span>
                                                @else
                                                    <span class="text-gray-600">{{ $fmt($old[$k] ?? null) }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">Belum ada aktivitas tercatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
</div>
</body>
</html>
