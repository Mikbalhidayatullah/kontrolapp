<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>{{ $title ?? 'Login' }}</title>
</head>
<body class="min-h-full bg-slate-100 text-slate-900">
    <div class="relative isolate flex min-h-screen items-center justify-center overflow-hidden px-4 py-8 sm:px-6">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(56,189,248,0.18),_transparent_30%),radial-gradient(circle_at_bottom_left,_rgba(16,185,129,0.14),_transparent_28%)]"></div>

        <section class="relative w-full max-w-md rounded-[32px] border border-slate-200 bg-white p-8 shadow-xl shadow-slate-900/5 sm:p-10">
            <div class="text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-3xl bg-gradient-to-br from-sky-400 via-cyan-300 to-emerald-300 text-lg font-black text-slate-950">
                    KA
                </div>
                <p class="mt-5 text-xs font-semibold uppercase tracking-[0.28em] text-sky-600">Kontrol App</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">Login</h1>
            </div>

            @if (session('status'))
                <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <ul class="space-y-1">
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
                    <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan password" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                </div>

                <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                    Ingat saya
                </label>

                <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700">
                    Login
                </button>
            </form>
        </section>
    </div>
</body>
</html>
