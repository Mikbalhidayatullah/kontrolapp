<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="border-b border-slate-200 pb-6">
                <p class="text-sm font-medium text-sky-600">Tambah Akun Baru</p>
                <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Buat user internal</h2>
                <p class="mt-2 text-sm text-slate-500">Pilih role sesuai kebutuhan kerja. User yang dibuat dari sini bisa langsung login tanpa proses registrasi publik.</p>
            </div>

            <form action="{{ route('users.store') }}" method="POST" class="mt-8 space-y-6">
                @csrf
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">Nama Lengkap</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                </div>

                <div>
                    <label for="role" class="block text-sm font-medium text-slate-700">Role</label>
                    <select id="role" name="role" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                        <input id="password" type="password" name="password" required class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Konfirmasi Password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                    </div>
                </div>

                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                    Akun aktif dan bisa langsung digunakan untuk login
                </label>

                <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
                    <a href="{{ route('users.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700">
                        Simpan User
                    </button>
                </div>
            </form>
        </section>

        <aside class="space-y-6">
            <article class="rounded-[28px] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-sky-950 p-6 text-white shadow-sm">
                <p class="text-sm font-medium text-sky-200">Aturan Role</p>
                <div class="mt-5 space-y-4">
                    <div class="rounded-2xl border border-white/10 bg-white/8 p-4">
                        <p class="font-medium">Administrator</p>
                        <p class="mt-1 text-sm text-slate-300">Akses semua halaman dan pengelolaan user.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/8 p-4">
                        <p class="font-medium">Bendahara</p>
                        <p class="mt-1 text-sm text-slate-300">Akses lembar kontrol, input data kontrol, dan report keuangan.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/8 p-4">
                        <p class="font-medium">Verifikator</p>
                        <p class="mt-1 text-sm text-slate-300">Akses halaman perjadin dan report perjadin.</p>
                    </div>
                </div>
            </article>

            <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-emerald-600">Catatan Keamanan</p>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                    <li>Password harus mengandung huruf besar, huruf kecil, angka, dan simbol.</li>
                    <li>Registrasi publik dimatikan sehingga hanya admin yang bisa menambah akun.</li>
                    <li>Akun bisa dinonaktifkan sewaktu-waktu tanpa harus dihapus permanen.</li>
                </ul>
            </article>
        </aside>
    </div>
</x-layout>
