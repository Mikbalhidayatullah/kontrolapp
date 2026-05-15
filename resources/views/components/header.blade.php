@php
    $currentPath = request()->path();
    $description = match (true) {
        request()->routeIs('dashboard') => 'Rekap data periode aktif.',
        request()->routeIs('lembar-kontrol') => 'Daftar transaksi kontrol.',
        request()->routeIs('add-data-kontrol') => 'Form input transaksi kontrol.',
        request()->routeIs('dana-saving.index') => 'Daftar dana saving.',
        request()->routeIs('dana-saving.create') => 'Form input dana saving.',
        request()->routeIs('dana-saving.edit') => 'Edit data dana saving.',
        request()->routeIs('perjadin') => 'Daftar perjalanan dinas.',
        request()->routeIs('add-perjadin') => 'Form input perjadin.',
        request()->routeIs('perjadin.show') => 'Detail data perjadin.',
        request()->routeIs('perjadin.edit') => 'Edit data perjadin.',
        request()->routeIs('report') => 'Ringkasan laporan.',
        request()->routeIs('users.index') => 'Kelola data user.',
        request()->routeIs('users.create') => 'Tambah user baru.',
        request()->routeIs('users.edit') => 'Edit data user.',
        default => 'Kelola data aplikasi.',
    };
@endphp

<header class="relative overflow-hidden border-b border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-sky-950">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(56,189,248,0.18),_transparent_30%),radial-gradient(circle_at_bottom_left,_rgba(16,185,129,0.14),_transparent_28%)]"></div>
    <div class="relative mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="max-w-3xl">
            <h1 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">{{ $slot }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">{{ $description }}</p>
        </div>
    </div>
</header>
