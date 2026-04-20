<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    @php
        $roleStyles = [
            'admin' => 'bg-rose-50 text-rose-700 ring-rose-200',
            'bendahara' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'verifikator' => 'bg-amber-50 text-amber-700 ring-amber-200',
        ];
    @endphp

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Total User</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $users->count() }}</p>
                <p class="mt-2 text-sm text-slate-500">Seluruh akun internal sistem</p>
            </article>
            <article class="rounded-3xl border border-rose-200 bg-gradient-to-br from-rose-50 to-white p-5 shadow-sm">
                <p class="text-sm font-medium text-rose-700">Administrator</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $users->where('role', 'admin')->count() }}</p>
                <p class="mt-2 text-sm text-slate-500">Akun dengan akses penuh</p>
            </article>
            <article class="rounded-3xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-5 shadow-sm">
                <p class="text-sm font-medium text-sky-700">Bendahara</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $users->where('role', 'bendahara')->count() }}</p>
                <p class="mt-2 text-sm text-slate-500">Akun operasional keuangan</p>
            </article>
            <article class="rounded-3xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm">
                <p class="text-sm font-medium text-amber-700">Verifikator</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $users->where('role', 'verifikator')->count() }}</p>
                <p class="mt-2 text-sm text-slate-500">Akun verifikasi perjadin</p>
            </article>
        </section>

        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-sky-600">Administrator Panel</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Daftar dan kontrol user</h2>
                    <p class="mt-2 text-sm text-slate-500">Registrasi publik dimatikan. User baru hanya bisa dibuat dari halaman ini oleh administrator.</p>
                </div>
                <a href="{{ route('users.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700">
                    Tambah User
                </a>
            </div>

            <div class="mt-8 overflow-hidden rounded-3xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-950 text-left text-slate-200">
                        <tr>
                            <th class="px-5 py-4 font-medium">Nama</th>
                            <th class="px-5 py-4 font-medium">Email</th>
                            <th class="px-5 py-4 font-medium">Role</th>
                            <th class="px-5 py-4 font-medium">Status</th>
                            <th class="px-5 py-4 font-medium">Dibuat</th>
                            <th class="px-5 py-4 font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($users as $userItem)
                            <tr class="align-top hover:bg-slate-50/80">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-slate-900">{{ $userItem->name }}</p>
                                    @if (auth()->id() === $userItem->id)
                                        <p class="mt-1 text-xs text-slate-400">Akun yang sedang digunakan</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ $userItem->email }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $roleStyles[$userItem->role] ?? 'bg-slate-50 text-slate-700 ring-slate-200' }}">
                                        {{ $userItem->roleLabel() }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $userItem->is_active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-1 ring-slate-200' }}">
                                        {{ $userItem->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-slate-600">{{ $userItem->created_at?->format('d M Y') }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('users.edit', $userItem) }}" class="inline-flex rounded-full bg-sky-50 px-3 py-1.5 text-xs font-medium text-sky-700 transition hover:bg-sky-100">
                                            Edit
                                        </a>
                                        <form action="{{ route('users.destroy', $userItem) }}" method="POST" onsubmit="return confirm('Hapus user ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex rounded-full bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 transition hover:bg-rose-100">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layout>
