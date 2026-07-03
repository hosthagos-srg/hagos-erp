<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Hagos ERP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-800">
@include('partials.sidebar')
<div class="lg:pl-60 min-h-screen">
    <div class="min-h-screen p-6">
        <header class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Kelola User</h1>
            <p class="text-gray-600 mt-2">Daftar akun yang bisa mengakses sistem.</p>
        </header>

        @if (session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-md px-4 py-2">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-md px-4 py-2">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-md px-4 py-2">{{ $errors->first() }}</div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Daftar user --}}
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="text-left px-4 py-3">Nama</th>
                            <th class="text-left px-4 py-3">Email</th>
                            <th class="text-right px-4 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($users as $u)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ $u->name }}
                                    @if ($u->id === auth()->id())
                                        <span class="ml-1 text-[10px] bg-indigo-100 text-indigo-700 rounded px-1.5 py-0.5 font-semibold">Anda</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $u->email }}</td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <button type="button" onclick="toggleEdit('edit-{{ $u->id }}')"
                                        class="text-indigo-600 hover:underline text-xs font-semibold">Edit</button>
                                    @if ($u->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.destroy', $u) }}" class="inline"
                                            onsubmit="return confirm('Hapus user {{ $u->name }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="ml-2 text-red-600 hover:underline text-xs font-semibold">Hapus</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            {{-- Baris edit (tersembunyi) --}}
                            <tr id="edit-{{ $u->id }}" class="hidden bg-gray-50">
                                <td colspan="3" class="px-4 py-4">
                                    <form method="POST" action="{{ route('users.update', $u) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                                        @csrf @method('PUT')
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Nama</label>
                                            <input type="text" name="name" value="{{ $u->name }}" required
                                                class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Email</label>
                                            <input type="email" name="email" value="{{ $u->email }}" required
                                                class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500 mb-1">Password baru <span class="text-gray-400">(opsional)</span></label>
                                            <input type="password" name="password" placeholder="Kosongkan jika tetap"
                                                class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm">
                                        </div>
                                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-1.5 rounded-md">Simpan</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Tambah user --}}
            <div class="bg-white rounded-xl shadow-sm p-5 h-fit">
                <h2 class="text-base font-bold text-gray-900 mb-4">Tambah User</h2>
                <form method="POST" action="{{ route('users.store') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2.5 rounded-md transition-colors">Tambah</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleEdit(id) {
    document.getElementById(id).classList.toggle('hidden');
}
</script>
</body>
</html>
