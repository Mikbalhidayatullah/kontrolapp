@php
    $currentPath = request()->path();
    $description = match (true) {
        request()->routeIs('dashboard') => 'Pantau saving, dana terpakai, hutang berjalan, dan aktivitas terbaru dari satu halaman.',
        request()->routeIs('lembar-kontrol') => 'Lihat daftar input kontrol harian dengan tampilan tabel yang lebih rapi dan mudah dibaca.',
        request()->routeIs('add-data-kontrol') => 'Masukkan data pengeluaran, penerimaan, dan bukti transaksi dengan form yang lebih terstruktur.',
        request()->routeIs('dana-saving.index') => 'Kelola input dana saving yang akan masuk ke Tabel 2.1 dan dipakai otomatis oleh dashboard rekap.',
        request()->routeIs('dana-saving.create') => 'Tambahkan sumber dan nominal dana saving untuk periode yang sedang dipakai pada dashboard.',
        request()->routeIs('dana-saving.edit') => 'Perbarui nominal atau sumber dana saving agar rekap Tabel 2.1 dan 2.2 ikut berubah.',
        request()->routeIs('perjadin') => 'Kelola kebutuhan perjalanan dinas dan verifikasi transaksi terkait dalam alur yang konsisten.',
        request()->routeIs('add-perjadin') => 'Masukkan data perjalanan dinas baru agar langsung tercatat ke database perjadin.',
        request()->routeIs('report') => 'Siapkan ringkasan laporan berdasarkan role pengguna, termasuk report perjadin untuk verifikator.',
        request()->routeIs('users.index') => 'Administrator bisa menambah, mengubah, menonaktifkan, dan menghapus user dari halaman ini.',
        request()->routeIs('users.create') => 'Tambahkan akun baru tanpa membuka registrasi publik, lalu tetapkan role yang sesuai.',
        request()->routeIs('users.edit') => 'Perbarui role, status akun, dan password user dengan kontrol yang lebih aman.',
        default => 'Kelola data keuangan harian dengan tampilan frontend yang konsisten.',
    };
@endphp

<header class="relative overflow-hidden border-b border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-sky-950">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(56,189,248,0.18),_transparent_30%),radial-gradient(circle_at_bottom_left,_rgba(16,185,129,0.14),_transparent_28%)]"></div>
    <div class="relative mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-200">Lembaran Kontrol</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl">{{ $slot }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">{{ $description }}</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-3xl border border-white/10 bg-white/8 px-5 py-4 backdrop-blur">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Role Aktif</p>
                    <p class="mt-2 text-lg font-semibold text-white">{{ auth()->user()?->roleLabel() ?? 'Guest' }}</p>
                    <p class="mt-1 text-sm text-slate-300">Navigasi akan menyesuaikan hak akses user yang sedang login.</p>
                </div>
                <div class="rounded-3xl border border-white/10 bg-white/8 px-5 py-4 backdrop-blur">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Keamanan</p>
                    <p class="mt-2 text-lg font-semibold text-white">Role-Based Access</p>
                    <p class="mt-1 text-sm text-slate-300">Login tanpa registrasi publik, session aman, dan kontrol halaman per role.</p>
                </div>
            </div>
        </div>
    </div>
</header>
