<x-layout>
    <x-slot:title>{{ $isVerifikator ? 'Report Perjadin' : $title }}</x-slot:title>

    <div class="space-y-6">
        <section class="grid gap-4 lg:grid-cols-3">
            @foreach ($cards as $card)
                <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $card['value'] }}</p>
                    <p class="mt-2 text-sm text-slate-500">{{ $card['note'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium {{ $isVerifikator ? 'text-amber-600' : 'text-sky-600' }}">
                    {{ $isVerifikator ? 'Report Verifikasi' : 'Ringkasan Keuangan' }}
                </p>
                <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">
                    {{ $isVerifikator ? 'Status laporan perjalanan dinas' : 'Pergerakan laporan dan transaksi' }}
                </h2>
                <p class="mt-2 text-sm text-slate-500">
                    {{ $isVerifikator
                        ? 'Halaman ini khusus menampilkan ringkasan verifikasi dan nilai perjadin dari database.'
                        : 'Halaman ini menampilkan ringkasan kontrol dana dan perjadin berdasarkan data transaksi yang tersimpan.' }}
                </p>

                <div class="mt-6 overflow-hidden rounded-3xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-950 text-left text-slate-200">
                            <tr>
                                <th class="px-5 py-4 font-medium">Periode</th>
                                <th class="px-5 py-4 font-medium">Kategori</th>
                                <th class="px-5 py-4 font-medium">Nominal</th>
                                <th class="px-5 py-4 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="px-5 py-4 text-slate-600">{{ $row['periode'] }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $row['kategori'] }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-900">{{ $row['nominal'] }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                            {{ $row['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-10 text-center text-slate-500">
                                        {{ $isVerifikator ? 'Belum ada data perjadin untuk direkap di report.' : 'Belum ada data transaksi yang bisa ditampilkan di report.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="rounded-[28px] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-sky-950 p-6 text-white shadow-sm">
                <p class="text-sm font-medium text-sky-200">Catatan Halaman</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight">Arah pengembangan berikutnya</h2>
                <div class="mt-5 space-y-4">
                    <div class="rounded-2xl border border-white/10 bg-white/8 p-4">
                        <p class="font-medium">Filter periode</p>
                        <p class="mt-1 text-sm text-slate-300">Bisa ditambahkan harian, mingguan, dan bulanan jika nanti kamu ingin report yang lebih detail.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/8 p-4">
                        <p class="font-medium">Export PDF / Excel</p>
                        <p class="mt-1 text-sm text-slate-300">Struktur tabel report ini sudah siap dilanjutkan ke fitur export.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/8 p-4">
                        <p class="font-medium">Statistik per role</p>
                        <p class="mt-1 text-sm text-slate-300">Admin dan bendahara fokus ke keuangan, verifikator fokus ke report perjadin.</p>
                    </div>
                </div>
            </article>
        </section>
    </div>
</x-layout>
