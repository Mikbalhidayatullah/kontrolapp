@php
    $user = auth()->user();
    $role = $user?->role;
    $navItems = [];
    $afterPerjadinNavItems = [];
    $perjadinSbuMenu = null;

    if (in_array($role, ['admin', 'bendahara'], true)) {
        $navItems[] = ['label' => 'Dashboard', 'href' => route('dashboard'), 'active' => request()->routeIs('dashboard')];
    }

    if (in_array($role, ['admin', 'bendahara'], true)) {
        $navItems[] = ['label' => 'Lembar Kontrol', 'href' => route('lembar-kontrol'), 'active' => request()->routeIs('lembar-kontrol')];
        $navItems[] = ['label' => 'Dana Saving', 'href' => route('dana-saving.index'), 'active' => request()->routeIs('dana-saving.*')];
    }

    if (in_array($role, ['admin', 'bendahara', 'verifikator'], true)) {
        $navItems[] = ['label' => 'Pajak', 'href' => route('pajak.index'), 'active' => request()->routeIs('pajak.*')];
    }

    if (in_array($role, ['admin', 'bendahara', 'verifikator'], true)) {
        $perjadinSbuItems = [
            ['label' => 'Perjadin', 'href' => route('perjadin'), 'active' => request()->routeIs('perjadin') || request()->routeIs('add-perjadin') || request()->routeIs('perjadin.*')],
        ];

        if (in_array($role, ['admin', 'bendahara'], true)) {
            $perjadinSbuItems[] = ['label' => 'Halaman Bayar', 'href' => route('perjadin-payments.index'), 'active' => request()->routeIs('perjadin-payments.*')];
        }

        $perjadinSbuItems[] = ['label' => 'SBU', 'href' => route('local-transport-sbus.index'), 'active' => request()->routeIs('local-transport-sbus.*')];

        $perjadinSbuMenu = [
            'label' => 'Perjadin & SBU',
            'active' => request()->routeIs('perjadin') || request()->routeIs('add-perjadin') || request()->routeIs('perjadin.*') || request()->routeIs('perjadin-payments.*') || request()->routeIs('local-transport-sbus.*'),
            'items' => $perjadinSbuItems,
        ];
    }

    if (in_array($role, ['admin', 'bendahara', 'verifikator'], true)) {
        $afterPerjadinNavItems[] = ['label' => $role === 'verifikator' ? 'Report Perjadin' : 'Report', 'href' => route('report'), 'active' => request()->routeIs('report')];
    }

    if ($role === 'admin') {
        $afterPerjadinNavItems[] = ['label' => 'Kelola User', 'href' => route('users.index'), 'active' => request()->routeIs('users.*')];
    }
@endphp

<nav class="sticky top-0 z-40 border-b border-white/10 bg-slate-950/95 text-white backdrop-blur">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-18 items-center justify-between gap-4 py-3">
            <div class="flex min-w-0 items-center gap-4">
                <a href="/dasborapp" class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-400 via-cyan-300 to-emerald-300 text-sm font-black text-slate-950 shadow-lg shadow-cyan-500/20">
                        KA
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold tracking-[0.24em] text-sky-200 uppercase">Kontrol App</p>
                        <p class="truncate text-xs text-slate-400">Kontrol dana harian dan saving</p>
                    </div>
                </a>
            </div>

            @auth
                <div class="hidden items-center gap-2 lg:flex">
                    @foreach ($navItems as $item)
                        <x-nav-link href="{{ $item['href'] }}" :active="$item['active']">{{ $item['label'] }}</x-nav-link>
                    @endforeach

                    @if ($perjadinSbuMenu)
                        <details class="group relative">
                            <summary
                                aria-current="{{ $perjadinSbuMenu['active'] ? 'page' : false }}"
                                class="{{ $perjadinSbuMenu['active'] ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }} inline-flex cursor-pointer list-none items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition [&::-webkit-details-marker]:hidden"
                            >
                                {{ $perjadinSbuMenu['label'] }}
                                <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 transition group-open:rotate-180" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </summary>
                            <div class="absolute left-0 top-full z-50 mt-2 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 text-slate-900 shadow-xl shadow-slate-950/20">
                                @foreach ($perjadinSbuMenu['items'] as $child)
                                    <a
                                        href="{{ $child['href'] }}"
                                        aria-current="{{ $child['active'] ? 'page' : false }}"
                                        class="{{ $child['active'] ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-950' }} block rounded-xl px-3 py-2 text-sm font-medium transition"
                                    >
                                        {{ $child['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </details>
                    @endif

                    @foreach ($afterPerjadinNavItems as $item)
                        <x-nav-link href="{{ $item['href'] }}" :active="$item['active']">{{ $item['label'] }}</x-nav-link>
                    @endforeach
                </div>

                <div class="hidden items-center gap-3 md:flex">
                    <div class="rounded-full border border-white/10 bg-white/5 px-4 py-2 text-right">
                        <p class="text-[11px] uppercase tracking-[0.22em] text-slate-400">Role</p>
                        <p class="text-sm font-semibold text-emerald-300">{{ $user->roleLabel() }}</p>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center rounded-full border border-white/10 bg-white/5 px-4 py-2 text-sm font-medium text-slate-100 transition hover:bg-white/10">
                            Logout
                        </button>
                    </form>
                </div>
            @else
                <div class="hidden md:flex">
                    <a href="{{ route('login') }}" class="inline-flex items-center rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-sky-100">
                        Login
                    </a>
                </div>
            @endauth

            <div class="lg:hidden">
                <button type="button" command="--toggle" commandfor="mobile-menu" class="inline-flex items-center justify-center rounded-2xl border border-white/10 bg-white/5 p-2 text-slate-200 hover:bg-white/10 focus:outline-none">
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

    <el-disclosure id="mobile-menu" hidden class="block border-t border-white/10 lg:hidden">
        <div class="space-y-2 px-4 py-4">
            @auth
                @foreach ($navItems as $item)
                    <a
                        href="{{ $item['href'] }}"
                        aria-current="{{ $item['active'] ? 'page' : false }}"
                        class="{{ $item['active'] ? 'bg-white text-slate-950' : 'bg-white/5 text-slate-200 hover:bg-white/10' }} block rounded-2xl px-4 py-3 text-sm font-medium transition"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach

                @if ($perjadinSbuMenu)
                    <details class="rounded-2xl bg-white/5">
                        <summary
                            aria-current="{{ $perjadinSbuMenu['active'] ? 'page' : false }}"
                            class="{{ $perjadinSbuMenu['active'] ? 'bg-white text-slate-950' : 'text-slate-200 hover:bg-white/10' }} flex cursor-pointer list-none items-center justify-between rounded-2xl px-4 py-3 text-sm font-medium transition [&::-webkit-details-marker]:hidden"
                        >
                            {{ $perjadinSbuMenu['label'] }}
                            <svg viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </summary>
                        <div class="space-y-1 px-2 pb-2 pt-1">
                            @foreach ($perjadinSbuMenu['items'] as $child)
                                <a
                                    href="{{ $child['href'] }}"
                                    aria-current="{{ $child['active'] ? 'page' : false }}"
                                    class="{{ $child['active'] ? 'bg-white text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }} block rounded-xl px-4 py-2.5 text-sm font-medium transition"
                                >
                                    {{ $child['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endif

                @foreach ($afterPerjadinNavItems as $item)
                    <a
                        href="{{ $item['href'] }}"
                        aria-current="{{ $item['active'] ? 'page' : false }}"
                        class="{{ $item['active'] ? 'bg-white text-slate-950' : 'bg-white/5 text-slate-200 hover:bg-white/10' }} block rounded-2xl px-4 py-3 text-sm font-medium transition"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach

                <form action="{{ route('logout') }}" method="POST" class="pt-2">
                    @csrf
                    <button type="submit" class="block w-full rounded-2xl bg-white/5 px-4 py-3 text-left text-sm font-medium text-slate-200 transition hover:bg-white/10">
                        Logout
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-950">
                    Login ke Sistem
                </a>
            @endauth
        </div>
    </el-disclosure>
</nav>


