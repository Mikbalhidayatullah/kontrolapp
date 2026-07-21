<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    @php
        $exportGroupsByMonth = $exportGroups->groupBy('monthKey');
    @endphp

    <div class="space-y-6">
        <section class="grid gap-4 lg:grid-cols-4">
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Belum Dicetak</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $summary['groupCount'] }}</p>
                <p class="mt-2 text-sm text-slate-500">Kelompok surat tugas menunggu cetak</p>
            </article>
            <article class="rounded-3xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-5 shadow-sm">
                <p class="text-sm font-medium text-sky-700">Penerima</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $summary['entryCount'] }}</p>
                <p class="mt-2 text-sm text-slate-500">Perjadin belum dicetak</p>
            </article>
            <article class="rounded-3xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm">
                <p class="text-sm font-medium text-emerald-700">Nominal Belum Dicetak</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">Rp {{ number_format($summary['grandTotal'], 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $periodLabel }}</p>
            </article>
            <article class="rounded-3xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm">
                <p class="text-sm font-medium text-amber-700">Sudah Dicetak</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $summary['printedGroupCount'] }}</p>
                <p class="mt-2 text-sm text-slate-500">Rp {{ number_format($summary['printedGrandTotal'], 0, ',', '.') }}</p>
            </article>
        </section>

        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-600">Halaman Bayar</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Daftar penerimaan perjalanan dinas</h2>
                    <p class="mt-2 text-sm text-slate-500">Default menampilkan semua bulan. Pilih filter jika ingin melihat bulan atau tahun tertentu.</p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <form action="{{ route('perjadin-payments.index') }}" method="GET" data-auto-submit-filter class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <select name="month" data-auto-submit-control class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                            <option value="" @selected($currentPeriod['month'] === null)>Semua bulan</option>
                            @foreach ($monthOptions as $month)
                                <option value="{{ $month['value'] }}" @selected($currentPeriod['month'] === $month['value'])>{{ $month['label'] }}</option>
                            @endforeach
                        </select>
                        <select name="year" data-auto-submit-control class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                            <option value="" @selected($currentPeriod['year'] === null)>Semua tahun</option>
                            @foreach ($yearOptions as $year)
                                <option value="{{ $year }}" @selected($currentPeriod['year'] === $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="keyword" value="{{ $selectedKeyword }}" placeholder="Cari nama, surat tugas, tujuan..." data-auto-submit-control data-auto-submit-delay="450" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100 sm:w-72" />
                        @if ($selectedKeyword !== '' || $currentPeriod['month'] !== null || $currentPeriod['year'] !== null)
                            <a href="{{ route('perjadin-payments.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-500 transition hover:border-slate-300 hover:text-slate-700">
                                Reset
                            </a>
                        @endif
                    </form>

                    <a href="#pilih-surat-tugas-export" class="inline-flex items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                        Pilih Surat Tugas
                    </a>
                </div>
            </div>
        </section>

        <section id="pilih-surat-tugas-export" class="rounded-[28px] border border-emerald-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-600">Download Excel</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Pilih nomor surat tugas belum dicetak</h2>
                    <p class="mt-2 text-sm text-slate-500">Checklist hanya menampilkan surat tugas yang belum ditekan tombol Sudah Dicetak.</p>
                </div>
            </div>

            @if ($exportGroups->isEmpty())
                <div class="mt-5 rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-6 py-8 text-center text-sm text-slate-500">
                    Tidak ada surat tugas belum dicetak yang bisa diunduh.
                </div>
            @else
                <form action="{{ route('perjadin-payments.export.xlsx') }}" method="POST" class="mt-5 space-y-4">
                    @csrf
                    <div class="max-h-[460px] space-y-4 overflow-y-auto pr-1">
                        @foreach ($exportGroupsByMonth as $monthGroups)
                            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-50">
                                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-3">
                                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $monthGroups->first()['monthLabel'] }}</p>
                                    <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Rp {{ number_format($monthGroups->sum('total'), 0, ',', '.') }}</span>
                                </div>
                                <div class="space-y-2 p-3">
                                    @foreach ($monthGroups as $exportGroup)
                                        @php
                                            $exportPaymentGroup = $exportGroup['paymentGroup'];
                                            $selectedPaymentGroupIds = collect(old('payment_group_ids', []))->map(fn ($id) => (int) $id);
                                        @endphp
                                        <label class="flex cursor-pointer flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-3 transition hover:border-emerald-300 hover:bg-emerald-50/60 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="flex gap-3">
                                                <input type="checkbox" name="payment_group_ids[]" value="{{ $exportPaymentGroup->id }}" @checked($selectedPaymentGroupIds->contains($exportPaymentGroup->id)) class="mt-1 h-5 w-5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                                <div>
                                                    <p class="font-semibold text-slate-900">{{ $exportPaymentGroup->assignment_number }}</p>
                                                    <p class="mt-1 text-sm text-slate-500">{{ optional($exportPaymentGroup->assignment_date)->translatedFormat('d M Y') }} &middot; {{ $exportGroup['destination'] }} &middot; {{ $exportGroup['periodLabel'] }}</p>
                                                    @if (blank($exportPaymentGroup->purpose))
                                                        <p class="mt-2 text-sm text-amber-700">Tujuan/kegiatan belum diisi</p>
                                                    @else
                                                        <details class="group mt-2">
                                                            <summary class="cursor-pointer list-none text-sm text-slate-600 transition hover:text-emerald-700 [&::-webkit-details-marker]:hidden">
                                                                <span class="line-clamp-1">{{ $exportPaymentGroup->purpose }}</span>
                                                                <span class="mt-1 inline-block text-xs font-semibold text-emerald-700 group-open:hidden">Lihat tujuan lengkap</span>
                                                                <span class="mt-1 hidden text-xs font-semibold text-emerald-700 group-open:inline-block">Tutup tujuan</span>
                                                            </summary>
                                                            <p class="mt-2 rounded-2xl border border-emerald-100 bg-white p-3 text-sm leading-6 text-slate-700">{{ $exportPaymentGroup->purpose }}</p>
                                                        </details>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex flex-wrap gap-2 sm:justify-end">
                                                <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">{{ $exportGroup['entries']->count() }} penerima</span>
                                                <span class="rounded-full border border-emerald-200 bg-white px-3 py-1 text-xs font-semibold text-emerald-700">Rp {{ number_format($exportGroup['total'], 0, ',', '.') }}</span>
                                                <span class="rounded-full border {{ blank($exportPaymentGroup->purpose) ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' }} px-3 py-1 text-xs font-semibold">
                                                    {{ blank($exportPaymentGroup->purpose) ? 'Belum lengkap' : 'Siap export' }}
                                                </span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                        Download Excel Pilihan
                    </button>
                </form>
            @endif
        </section>

        <section class="space-y-5">
            <div>
                <p class="text-sm font-medium text-amber-600">Belum Dicetak</p>
                <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Surat tugas menunggu cetak</h2>
            </div>

            @if ($pendingMonthGroups->isEmpty())
                <div class="rounded-[28px] border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm">
                    <p class="text-lg font-semibold text-slate-900">Tidak ada perjadin belum dicetak pada filter ini.</p>
                    <p class="mt-2 text-sm text-slate-500">Klik tombol Bayar di tabel Perjadin agar data masuk ke halaman ini.</p>
                </div>
            @else
                @foreach ($pendingMonthGroups as $monthGroup)
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-3xl border border-amber-200 bg-amber-50 px-5 py-3">
                            <p class="text-sm font-bold uppercase tracking-[0.2em] text-amber-700">{{ $monthGroup['label'] }}</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600">{{ $monthGroup['entryCount'] }} penerima</span>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-700">Rp {{ number_format($monthGroup['total'], 0, ',', '.') }}</span>
                            </div>
                        </div>

                        @foreach ($monthGroup['groups'] as $group)
                            @php($paymentGroup = $group['paymentGroup'])
                            <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                                <div class="border-b border-slate-200 bg-slate-50 p-5">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">{{ optional($paymentGroup->assignment_date)->translatedFormat('d M Y') }}</span>
                                                <span class="rounded-full border {{ blank($paymentGroup->purpose) ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' }} px-3 py-1 text-xs font-semibold">
                                                    {{ blank($paymentGroup->purpose) ? 'Tujuan belum diisi' : 'Siap export' }}
                                                </span>
                                            </div>
                                            <h3 class="mt-3 min-w-0 text-xl font-semibold text-slate-900">{{ $paymentGroup->assignment_number }}</h3>
                                            <p class="mt-2 text-sm text-slate-500">Tempat: {{ $group['destination'] }} &middot; Waktu: {{ $group['periodLabel'] }}</p>
                                        </div>
                                        <div class="shrink-0 text-left lg:text-right">
                                            <p class="text-xs uppercase tracking-[0.18em] text-emerald-600">{{ $group['entries']->count() }} penerima</p>
                                            <p class="mt-1 text-2xl font-semibold leading-none text-slate-900">Rp {{ number_format($group['total'], 0, ',', '.') }}</p>
                                            <form action="{{ route('perjadin-payments.mark-printed', $paymentGroup) }}" method="POST" class="mt-3" onsubmit="return confirm('Tandai surat tugas ini sudah dicetak? Setelah itu checklist export akan hilang.');">
                                                @csrf
                                                <input type="hidden" name="month" value="{{ $currentPeriod['month'] }}">
                                                <input type="hidden" name="year" value="{{ $currentPeriod['year'] }}">
                                                <input type="hidden" name="keyword" value="{{ $selectedKeyword }}">
                                                <button type="submit" class="inline-flex items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 px-4 py-2.5 text-sm font-semibold text-sky-700 transition hover:bg-sky-100">
                                                    Sudah Dicetak
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid gap-5 p-5 xl:grid-cols-[minmax(320px,0.8fr)_minmax(0,1.2fr)]">
                                    <div class="space-y-3">
                                        <p class="text-sm font-semibold text-slate-900">Tujuan / Kegiatan Surat Tugas</p>
                                        @if (blank($paymentGroup->purpose))
                                            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                                                <p class="text-sm leading-6 text-amber-700">Tujuan/kegiatan belum diisi. Isi atau ubah tujuan melalui Tambah/Edit Perjadin.</p>
                                            </div>
                                        @else
                                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 px-4 py-3">
                                                <p class="max-h-20 overflow-hidden text-sm leading-6 text-slate-700">{{ $paymentGroup->purpose }}</p>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="overflow-hidden rounded-3xl border border-slate-200">
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                                <thead class="bg-slate-950 text-left text-slate-200">
                                                    <tr>
                                                        <th class="px-4 py-3 font-medium">Nama</th>
                                                        <th class="px-4 py-3 font-medium">Golongan</th>
                                                        <th class="px-4 py-3 font-medium">Asal Instansi</th>
                                                        <th class="px-4 py-3 font-medium">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100 bg-white">
                                                    @foreach ($group['entries'] as $entry)
                                                        <tr>
                                                            <td class="px-4 py-3 align-top">
                                                                <p class="font-semibold text-slate-900">{{ $entry->executor_name }}</p>
                                                                <p class="mt-1 text-xs text-slate-500">{{ $entry->position_name ?: '-' }}</p>
                                                            </td>
                                                            <td class="px-4 py-3 align-top text-slate-600">{{ $entry->grade ?: '-' }}</td>
                                                            <td class="px-4 py-3 align-top text-slate-600">{{ $entry->skpd_name ?: '-' }}</td>
                                                            <td class="px-4 py-3 align-top font-semibold text-slate-900">Rp {{ number_format($entry->grand_total, 0, ',', '.') }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        @endforeach
                    </div>
                @endforeach
            @endif
        </section>

        <section class="space-y-5">
            <div>
                <p class="text-sm font-medium text-emerald-600">Sudah Dicetak</p>
                <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Riwayat surat tugas yang sudah dicetak</h2>
            </div>

            @if ($printedMonthGroups->isEmpty())
                <div class="rounded-[28px] border border-dashed border-slate-300 bg-white px-6 py-10 text-center shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Belum ada surat tugas yang ditandai sudah dicetak pada filter ini.</p>
                </div>
            @else
                @foreach ($printedMonthGroups as $monthGroup)
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-3">
                            <p class="text-sm font-bold uppercase tracking-[0.2em] text-emerald-700">{{ $monthGroup['label'] }}</p>
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600">{{ $monthGroup['entryCount'] }} penerima</span>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-emerald-700">Rp {{ number_format($monthGroup['total'], 0, ',', '.') }}</span>
                            </div>
                        </div>

                        @foreach ($monthGroup['groups'] as $group)
                            @php($paymentGroup = $group['paymentGroup'])
                            <article class="rounded-[28px] border border-emerald-200 bg-white p-5 shadow-sm">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Sudah dicetak</span>
                                            <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600">{{ optional($paymentGroup->assignment_date)->translatedFormat('d M Y') }}</span>
                                        </div>
                                        <h3 class="mt-3 text-xl font-semibold text-slate-900">{{ $paymentGroup->assignment_number }}</h3>
                                        <p class="mt-2 text-sm text-slate-500">Tempat: {{ $group['destination'] }} &middot; Waktu: {{ $group['periodLabel'] }}</p>
                                    </div>
                                    <div class="text-left lg:text-right">
                                        <p class="text-xs uppercase tracking-[0.18em] text-emerald-600">{{ $group['entries']->count() }} penerima</p>
                                        <p class="mt-1 text-2xl font-semibold leading-none text-slate-900">Rp {{ number_format($group['total'], 0, ',', '.') }}</p>
                                        @if ($group['printedAt'])
                                            <p class="mt-2 text-xs text-slate-400">Dicetak {{ optional($group['printedAt'])->translatedFormat('d M Y H:i') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endforeach
            @endif
        </section>
    </div>
</x-layout>
