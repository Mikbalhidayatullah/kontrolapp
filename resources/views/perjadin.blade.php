<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    @php
        $statusStyles = [
            'Menunggu Verifikasi' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'Terverifikasi' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'Butuh Revisi Bukti' => 'bg-rose-50 text-rose-700 ring-rose-200',
        ];
    @endphp

    <div class="space-y-6">
        <section class="grid gap-4 lg:grid-cols-3">
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Total Dokumen</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $summary['totalCount'] }}</p>
                <p class="mt-2 text-sm text-slate-500">Seluruh pengajuan perjadin yang tersimpan</p>
            </article>
            <article class="rounded-3xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm">
                <p class="text-sm font-medium text-amber-700">Menunggu Verifikasi</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $summary['waitingVerification'] }}</p>
                <p class="mt-2 text-sm text-slate-500">Perlu dicek bukti dan kelengkapan</p>
            </article>
            <article class="rounded-3xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm">
                <p class="text-sm font-medium text-emerald-700">Total Anggaran</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">Rp {{ number_format($summary['totalBudget'], 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">Akumulasi budget seluruh data perjadin</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-amber-600">Monitoring Perjadin</p>
                        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Daftar pengajuan terbaru</h2>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('add-perjadin') }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700">
                            Tambah Perjadin
                        </a>
                        <a href="{{ route('report') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700">
                            Lihat Report
                        </a>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($entries as $entry)
                        <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <p class="text-lg font-semibold text-slate-900">{{ $entry->traveler_name }}</p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $entry->destination_city }} • {{ optional($entry->departure_date)->translatedFormat('d M Y') }} s/d {{ optional($entry->return_date)->translatedFormat('d M Y') }}
                                    </p>
                                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $entry->purpose }}</p>
                                </div>
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium ring-1 {{ $statusStyles[$entry->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                    {{ $entry->status }}
                                </span>
                            </div>
                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Transport</p>
                                    <p class="mt-1 text-sm font-medium text-slate-700">{{ $entry->transport_type }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Budget</p>
                                    <p class="mt-1 text-sm font-medium text-slate-700">Rp {{ number_format($entry->budget_amount, 0, ',', '.') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Terverifikasi</p>
                                    <p class="mt-1 text-sm font-medium text-slate-700">Rp {{ number_format($entry->verified_amount, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center text-slate-500">
                            Belum ada data perjadin yang tersimpan. Tambahkan pengajuan pertama dari form tambah perjadin.
                        </div>
                    @endforelse
                </div>
            </article>

            <article class="rounded-[28px] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-sky-950 p-6 text-white shadow-sm">
                <p class="text-sm font-medium text-sky-200">Checklist Verifikator</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-tight">Yang perlu diperhatikan</h2>
                <div class="mt-5 space-y-4">
                    <div class="rounded-2xl border border-white/10 bg-white/8 p-4">
                        <p class="font-medium">Bukti transport</p>
                        <p class="mt-1 text-sm text-slate-300">Pastikan tiket, invoice, atau bukti pembayaran sudah terlampir pada pengajuan.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/8 p-4">
                        <p class="font-medium">Kesesuaian tanggal</p>
                        <p class="mt-1 text-sm text-slate-300">Periksa keberangkatan dan kepulangan agar sesuai dengan maksud perjalanan dinas.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/8 p-4">
                        <p class="font-medium">Nominal verifikasi</p>
                        <p class="mt-1 text-sm text-slate-300">Gunakan kolom nominal terverifikasi untuk mencatat hasil pengecekan akhir.</p>
                    </div>
                </div>
            </article>
        </section>
    </div>
</x-layout>
