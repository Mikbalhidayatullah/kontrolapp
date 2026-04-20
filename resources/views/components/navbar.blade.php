@php
    $user = auth()->user();
    $role = $user?->role;
    $navItems = [];

    if (in_array($role, ['admin', 'bendahara'], true)) {
        $navItems[] = ['label' => 'Dashboard', 'href' => route('dashboard'), 'active' => request()->routeIs('dashboard')];
        $navItems[] = ['label' => 'Lembar Kontrol', 'href' => route('lembar-kontrol'), 'active' => request()->routeIs('lembar-kontrol')];
        $navItems[] = ['label' => 'Dana Saving', 'href' => route('dana-saving.index'), 'active' => request()->routeIs('dana-saving.*')];
        $navItems[] = ['label' => 'Tambah Data', 'href' => route('add-data-kontrol'), 'active' => request()->routeIs('add-data-kontrol')];
    }

    if (in_array($role, ['admin', 'verifikator'], true)) {
        $navItems[] = ['label' => 'Perjadin', 'href' => route('perjadin'), 'active' => request()->routeIs('perjadin')];
        $navItems[] = ['label' => 'Tambah Perjadin', 'href' => route('add-perjadin'), 'active' => request()->routeIs('add-perjadin')];
    }

    if (in_array($role, ['admin', 'bendahara', 'verifikator'], true)) {
        $navItems[] = ['label' => $role === 'verifikator' ? 'Report Perjadin' : 'Report', 'href' => route('report'), 'active' => request()->routeIs('report')];
    }

    if ($role === 'admin') {
        $navItems[] = ['label' => 'Kelola User', 'href' => route('users.index'), 'active' => request()->routeIs('users.*')];
    }
@endphp

<nav class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-18 items-center justify-between gap-4 py-3">
            <div class="flex min-w-0 items-center gap-4">
                <a href="/dasborapp" class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-400 via-cyan-300 to-emerald-300 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20">
                        KA
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold tracking-[0.24em] text-slate-900 uppercase">Kontrol App</p>
                    </div>
                </a>
            </div>

            @auth
                <div class="hidden items-center gap-2 lg:flex">
                    @foreach ($navItems as $item)
                        <x-nav-link href="{{ $item['href'] }}" :active="$item['active']">{{ $item['label'] }}</x-nav-link>
                    @endforeach
                </div>

                <div class="hidden items-center gap-3 md:flex">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-100">
                            Logout
                        </button>
                    </form>
                </div>
            @else
                <div class="hidden md:flex">
                    <a href="{{ route('login') }}" class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700">
                        Login
                    </a>
                </div>
            @endauth

            <div class="lg:hidden">
                <button type="button" command="--toggle" commandfor="mobile-menu" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 p-2 text-slate-700 hover:bg-slate-100 focus:outline-none">
                    <span class="sr-only">Buka menu navigasi</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6 in-aria-expanded:hidden">
                        <path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6 not-in-aria-expanded:hidden">
                        <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <el-disclosure id="mobile-menu" hidden class="block border-t border-slate-200 lg:hidden">
        <div class="space-y-2 px-4 py-4">
            @auth
                @foreach ($navItems as $item)
                    <a
                        href="{{ $item['href'] }}"
                        aria-current="{{ $item['active'] ? 'page' : false }}"
                        class="{{ $item['active'] ? 'bg-slate-900 text-white' : 'bg-slate-50 text-slate-700 hover:bg-slate-100' }} block rounded-2xl px-4 py-3 text-sm font-medium transition"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach

                <form action="{{ route('logout') }}" method="POST" class="pt-2">
                    @csrf
                    <button type="submit" class="block w-full rounded-2xl bg-slate-50 px-4 py-3 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                        Logout
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">
                    Login
                </a>
            @endauth
        </div>
    </el-disclosure>
</nav>
