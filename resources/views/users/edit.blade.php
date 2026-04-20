<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="border-b border-slate-200 pb-6">
                <p class="text-sm font-medium text-sky-600">Edit User</p>
                <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ $userData->name }}</h2>
                <p class="mt-2 text-sm text-slate-500">Perbarui role, email, password, atau status akun tanpa mengubah alur login publik.</p>
            </div>

            <form action="{{ route('users.update', $userData) }}" method="POST" class="mt-8 space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">Nama Lengkap</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $userData->name) }}" required class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $userData->email) }}" required class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                </div>

                <div>
                    <label for="role" class="block text-sm font-medium text-slate-700">Role</label>
                    <select id="role" name="role" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', $userData->role) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700">Password Baru</label>
                        <input id="password" type="password" name="password" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        <p class="mt-2 text-xs text-slate-400">Kosongkan bila tidak ingin mengganti password.</p>
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Konfirmasi Password Baru</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                    </div>
                </div>

                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $userData->is_active)) class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                    Akun aktif dan diizinkan login
                </label>

                <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
                    <a href="{{ route('users.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                        Kembali
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700">
                        Update User
                    </button>
                </div>
            </form>
        </section>

        <aside class="space-y-6">
            <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-amber-600">Status Saat Ini</p>
                <div class="mt-4 space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Role</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ $userData->roleLabel() }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Status Login</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ $userData->is_active ? 'Aktif' : 'Nonaktif' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Dibuat</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ $userData->created_at?->format('d M Y') }}</p>
                    </div>
                </div>
            </article>

            <article class="rounded-[28px] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-sky-950 p-6 text-white shadow-sm">
                <p class="text-sm font-medium text-sky-200">Pengaman Admin</p>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-300">
                    <li>Akun admin terakhir tidak bisa dihapus atau diganti menjadi role lain.</li>
                    <li>Akun yang sedang dipakai tidak bisa dinonaktifkan atau dihapus sendiri.</li>
                    <li>Perubahan password tetap memakai hashing bawaan Laravel.</li>
                </ul>
            </article>
        </aside>
    </div>
</x-layout>
