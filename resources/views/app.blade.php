<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>{{ $title ?? 'Login' }}</title>
</head>
<body class="min-h-full bg-slate-100 text-slate-900">
    <div class="relative isolate min-h-screen overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(56,189,248,0.18),_transparent_30%),radial-gradient(circle_at_bottom_left,_rgba(16,185,129,0.14),_transparent_28%)]"></div>

        <div class="relative mx-auto flex min-h-screen max-w-7xl items-center justify-center px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
            <section class="flex w-full max-w-xl items-center">
                <div class="w-full rounded-[32px] border border-slate-200 bg-white p-8 shadow-xl shadow-slate-900/5 sm:p-10">
                    <div class="flex flex-col items-center justify-center gap-4 text-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-3xl bg-gradient-to-br from-sky-400 via-cyan-300 to-emerald-300 text-lg font-black text-slate-950">
                            KA
                        </div>
                        <div>
                            <p class="text-sm font-semibold tracking-[0.24em] text-sky-600 uppercase">Kontrol App</p>
                            <p class="mt-1 text-sm text-slate-500">Lembaran kontrol dana, saving, dan verifikasi transaksi</p>
                        </div>
                    </div>

                    <div class="mt-8 text-center">
                        <h2 class="text-3xl font-semibold tracking-tight text-slate-900">Login akun</h2>
                    </div>

                    @if (session('status'))
                        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            <p class="font-semibold">Login belum berhasil.</p>
                            <ul class="mt-2 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('login.store') }}" method="POST" class="mt-8 space-y-6">
                        @csrf
                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="nama@kontrol-app.test" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                                <span class="text-xs text-slate-400">Minimal 8 karakter</span>
                            </div>
                            <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan password" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>

                        <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                            Ingat sesi login pada perangkat ini
                        </label>

                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700">
                            Login ke Dashboard
                        </button>
                    </form>

                </div>
            </section>
        </div>
    </div>
</body>
</html>
