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

        <div class="relative mx-auto grid min-h-screen max-w-7xl gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[1.1fr_0.9fr] lg:px-8 lg:py-12">
            <section class="overflow-hidden rounded-[32px] bg-gradient-to-br from-slate-950 via-slate-900 to-sky-950 p-8 text-white shadow-2xl shadow-slate-900/15 sm:p-10">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-3xl bg-gradient-to-br from-sky-400 via-cyan-300 to-emerald-300 text-lg font-black text-slate-950">
                        KA
                    </div>
                    <div>
                        <p class="text-sm font-semibold tracking-[0.24em] text-sky-200 uppercase">Kontrol App</p>
                        <p class="text-sm text-slate-300">Lembaran kontrol dana, saving, dan verifikasi transaksi</p>
                    </div>
                </div>

                <div class="mt-10 max-w-2xl">
                    <p class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium tracking-[0.24em] text-sky-100 uppercase">
                        Login Aman
                    </p>
                    <h1 class="mt-5 text-4xl font-semibold tracking-tight sm:text-5xl">
                        Satu pintu masuk untuk admin, bendahara, dan verifikator.
                    </h1>
                    <p class="mt-4 text-base leading-7 text-slate-300">
                        Sistem ini tidak menyediakan registrasi publik. Akun hanya dibuat oleh administrator agar kontrol akses tetap aman saat aplikasi mulai dipakai penuh dengan database dan data transaksi nyata.
                    </p>
                </div>

                <div class="mt-10 grid gap-4 md:grid-cols-3">
                    <div class="rounded-3xl border border-white/10 bg-white/8 p-5 backdrop-blur">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Administrator</p>
                        <p class="mt-2 text-lg font-semibold">Akses penuh</p>
                        <p class="mt-2 text-sm text-slate-300">Dashboard, kontrol dana, perjadin, report, dan CRUD user.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/8 p-5 backdrop-blur">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Bendahara</p>
                        <p class="mt-2 text-lg font-semibold">Operasional harian</p>
                        <p class="mt-2 text-sm text-slate-300">Lembar kontrol, input data kontrol, dan report keuangan.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/8 p-5 backdrop-blur">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Verifikator</p>
                        <p class="mt-2 text-lg font-semibold">Verifikasi perjadin</p>
                        <p class="mt-2 text-sm text-slate-300">Halaman perjadin dan report perjadin sesuai kebutuhan verifikasi.</p>
                    </div>
                </div>

                <div class="mt-10 rounded-[28px] border border-white/10 bg-white/8 p-6 backdrop-blur">
                    <h2 class="text-lg font-semibold">Lapisan keamanan yang sudah disiapkan</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-white/10 bg-black/10 p-4">
                            <p class="font-medium">Tanpa registrasi publik</p>
                            <p class="mt-1 text-sm text-slate-300">Akun hanya dibuat oleh admin melalui panel kelola user.</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-black/10 p-4">
                            <p class="font-medium">Role-based access</p>
                            <p class="mt-1 text-sm text-slate-300">Setiap halaman dibatasi sesuai role user yang login.</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-black/10 p-4">
                            <p class="font-medium">Session regeneration</p>
                            <p class="mt-1 text-sm text-slate-300">Session di-regenerate saat login dan diinvalidasi saat logout.</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-black/10 p-4">
                            <p class="font-medium">Password kuat</p>
                            <p class="mt-1 text-sm text-slate-300">User admin wajib memakai password dengan huruf besar, angka, dan simbol.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="flex items-center">
                <div class="w-full rounded-[32px] border border-slate-200 bg-white p-8 shadow-xl shadow-slate-900/5 sm:p-10">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-sky-600">Masuk ke Sistem</p>
                            <h2 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Login akun</h2>
                        </div>
                        <div class="rounded-full bg-slate-100 px-4 py-2 text-sm font-medium text-slate-600">
                            Role Secure
                        </div>
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

                    @if (app()->environment('local'))
                        <div class="mt-8 rounded-[28px] border border-slate-200 bg-slate-50 p-5">
                            <p class="text-sm font-semibold text-slate-900">Akun contoh untuk development lokal</p>
                            <p class="mt-1 text-sm text-slate-500">Ganti password ini setelah pengujian awal selesai.</p>
                            <div class="mt-4 space-y-3 text-sm text-slate-600">
                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                    <p class="font-medium text-slate-900">Admin</p>
                                    <p class="mt-1">`admin@kontrol-app.test` / `Admin#2026!`</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                    <p class="font-medium text-slate-900">Bendahara</p>
                                    <p class="mt-1">`bendahara@kontrol-app.test` / `Bendahara#2026!`</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                    <p class="font-medium text-slate-900">Verifikator</p>
                                    <p class="mt-1">`verifikator@kontrol-app.test` / `Verifikator#2026!`</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</body>
</html>
