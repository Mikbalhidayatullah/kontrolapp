<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    <div class="space-y-6">
        <section class="grid gap-4 lg:grid-cols-4">
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Total Dokumen</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $summary['totalCount'] }}</p>
                <p class="mt-2 text-sm text-slate-500">Data perjadin pada filter aktif</p>
            </article>
            <article class="rounded-3xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-5 shadow-sm">
                <p class="text-sm font-medium text-sky-700">Total Nominal</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">Rp {{ number_format($summary['totalGrandTotal'], 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">Grand total seluruh komponen SPPD</p>
            </article>
            <article class="rounded-3xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm">
                <p class="text-sm font-medium text-emerald-700">Dokumen Lengkap</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $summary['completeDocuments'] }}</p>
                <p class="mt-2 text-sm text-slate-500">Sudah ada PDF kegiatan dan bukti nota</p>
            </article>
            <article class="rounded-3xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm">
                <p class="text-sm font-medium text-amber-700">Perjadin Terbayarkan</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $summary['paidCount'] }}</p>
                <p class="mt-2 text-sm text-slate-500">Rp {{ number_format($summary['paidGrandTotal'], 0, ',', '.') }} sudah dibayar</p>
            </article>
        </section>

        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-medium text-sky-600">Daftar Perjadin</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Data dipisah per kategori perjalanan dinas</h2>
                    <p class="mt-2 text-sm text-slate-500">{{ $periodLabel }}</p>
                </div>

                <div class="flex flex-col gap-3">
                    <form action="{{ route('perjadin') }}" method="GET" data-auto-submit-filter class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <select id="month" name="month" data-auto-submit-control class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                            @foreach ($monthOptions as $month)
                                <option value="{{ $month['value'] }}" @selected($currentPeriod['month'] === $month['value'])>{{ $month['label'] }}</option>
                            @endforeach
                        </select>
                        <select id="year" name="year" data-auto-submit-control class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                            @foreach ($yearOptions as $year)
                                <option value="{{ $year }}" @selected($currentPeriod['year'] === $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                        <select id="category" name="category" data-auto-submit-control class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100 sm:w-72">
                            <option value="">Semua kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}" @selected($selectedCategory === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="keyword" value="{{ $selectedKeyword }}" placeholder="Cari nama, surat tugas, tujuan..." data-auto-submit-control data-auto-submit-delay="450" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100 sm:w-72" />
                        @if ($selectedCategory !== '' || $selectedKeyword !== '')
                            <a href="{{ route('perjadin', ['month' => $currentPeriod['month'], 'year' => $currentPeriod['year']]) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-500 transition hover:border-slate-300 hover:text-slate-700">
                                Reset
                            </a>
                        @endif
                    </form>

                    <div class="flex justify-end">
                        <div class="flex flex-wrap gap-3">
                            <details class="group relative">
                                <summary class="inline-flex cursor-pointer list-none items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 [&::-webkit-details-marker]:hidden">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true">
                                        <path d="M12 3v12m0 0 4-4m-4 4-4-4" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M5 19h14" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    Download Excel
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 transition group-open:rotate-180" aria-hidden="true">
                                        <path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </summary>
                                <div class="absolute right-0 z-30 mt-2 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white py-2 shadow-xl">
                                    <a href="{{ route('perjadin.export.xlsx') }}" class="block px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700">
                                        Custom (Format Lama)
                                    </a>
                                    <a href="{{ route('perjadin.export.bpk.xlsx') }}" class="block px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700">
                                        Versi BPK
                                    </a>
                                </div>
                            </details>
                            <a href="{{ route('add-perjadin', ['month' => $currentPeriod['month'], 'year' => $currentPeriod['year'], 'category' => $selectedCategory, 'keyword' => $selectedKeyword]) }}" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                                Tambah Perjadin
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-3">
            @foreach ($categorySummary as $item)
                <article class="rounded-3xl border {{ $selectedCategory === $item['label'] ? 'border-sky-300 bg-sky-50/60' : 'border-slate-200 bg-white' }} p-5 shadow-sm">
                    <p class="text-sm font-medium {{ $selectedCategory === $item['label'] ? 'text-sky-700' : 'text-slate-500' }}">{{ $item['label'] }}</p>
                    <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $item['count'] }} data</p>
                    <p class="mt-2 text-sm font-medium text-slate-700">Rp {{ number_format($item['grand_total'], 0, ',', '.') }}</p>
                    <p class="mt-2 text-sm text-slate-500">{{ $item['complete_count'] }} dokumen lengkap pada periode aktif</p>
                </article>
            @endforeach
        </section>

        <div class="space-y-6">
            @foreach ($groupedEntries as $group)
                @if ($selectedCategory !== '' && $selectedCategory !== $group['label'])
                    @continue
                @endif

                <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-sm font-medium text-amber-600">Kategori</p>
                            <h3 class="mt-1 text-xl font-semibold text-slate-900">{{ $group['label'] }}</h3>
                        </div>
                        <div class="flex flex-wrap gap-3 text-sm">
                            <span class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-slate-600">{{ $group['count'] }} data</span>
                            <span class="rounded-full border border-sky-200 bg-sky-50 px-4 py-2 font-medium text-sky-700">Rp {{ number_format($group['grand_total'], 0, ',', '.') }}</span>
                        </div>
                    </div>

                    @if ($group['count'] === 0)
                        <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                            Belum ada data pada kategori ini.
                        </div>
                    @else
                        <div class="mt-5 overflow-hidden rounded-3xl border border-slate-200">
                            <div class="{{ $group['count'] > 6 ? 'max-h-[470px] overflow-y-auto' : '' }}">
                            <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="sticky top-0 z-10 bg-slate-950 text-left text-slate-200">
                                    <tr>
                                        <th class="px-4 py-3 font-medium">Pelaksana</th>
                                        <th class="px-4 py-3 font-medium">Surat Tugas</th>
                                        <th class="px-4 py-3 font-medium">Periode</th>
                                        <th class="px-4 py-3 font-medium">Rincian Aktif</th>
                                        <th class="px-4 py-3 font-medium">Grand Total</th>
                                        <th class="px-4 py-3 font-medium">Pembayaran</th>
                                        <th class="px-4 py-3 font-medium">Lampiran</th>
                                        <th class="px-4 py-3 font-medium">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($group['items'] as $entry)
                                        @php
                                            $activeGroups = collect([
                                                ['enabled' => $entry->daily_allowance_enabled, 'label' => 'Uang Harian'],
                                                ['enabled' => $entry->representation_enabled, 'label' => 'Representasi'],
                                                ['enabled' => $entry->ticket_enabled, 'label' => 'Tiket'],
                                                ['enabled' => $entry->lodging_enabled, 'label' => 'Penginapan'],
                                                ['enabled' => $entry->local_transport_enabled, 'label' => 'Transport Lokal'],
                                                ['enabled' => $entry->other_cost_enabled, 'label' => 'Lain-lain'],
                                            ])->filter(fn ($item) => $item['enabled'])->pluck('label');
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-4 align-top">
                                                <p class="font-semibold text-slate-900">{{ $entry->executor_name }}</p>
                                                <p class="mt-1 text-slate-500">{{ $entry->position_name }} / Gol. {{ $entry->grade }}</p>
                                                <p class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-400">{{ $entry->skpd_name }}</p>
                                            </td>
                                            <td class="px-4 py-4 align-top text-slate-600">
                                                <p class="font-medium text-slate-900">{{ $entry->assignment_number }}</p>
                                                <p class="mt-1">{{ optional($entry->assignment_date)->translatedFormat('d M Y') }}</p>
                                                <p class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-400">{{ $entry->destination_city }}</p>
                                            </td>
                                            <td class="px-4 py-4 align-top text-slate-600">
                                                {{ optional($entry->start_date)->translatedFormat('d M Y') }}<br>
                                                <span class="text-xs text-slate-400">s/d {{ optional($entry->end_date)->translatedFormat('d M Y') }}</span>
                                            </td>
                                            <td class="px-4 py-4 align-top">
                                                <div class="flex flex-wrap gap-2">
                                                    @forelse ($activeGroups as $label)
                                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">{{ $label }}</span>
                                                    @empty
                                                        <span class="text-slate-400">Belum ada rincian biaya aktif</span>
                                                    @endforelse
                                                </div>
                                                <div class="mt-3 space-y-1 text-xs text-slate-400">
                                                    <p>Ditambahkan oleh {{ $entry->creator?->name ?? 'Akun tidak diketahui' }}</p>
                                                    @if ($entry->updated_by && $entry->updater)
                                                        <p>Terakhir diedit oleh {{ $entry->updater->name }}</p>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 align-top font-semibold text-slate-900">
                                                Rp {{ number_format($entry->grand_total, 0, ',', '.') }}
                                                @if ($entry->sbu_comparison_summary)
                                                    <div class="mt-2">
                                                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-medium leading-none {{ $entry->sbu_comparison_summary['tone'] }}">
                                                            {{ $entry->sbu_comparison_summary['label'] }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 align-top">
                                                @if ($entry->paid_at)
                                                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                        Sudah dibayar
                                                    </span>
                                                    <p class="mt-2 text-xs text-slate-400">{{ optional($entry->paid_at)->translatedFormat('d M Y') }}</p>
                                                @else
                                                    <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                                                        Belum dibayar
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 align-top">
                                                <div class="space-y-2">
                                                    @if ($entry->activity_file_path)
                                                        <a href="{{ route('perjadin.attachments.show', [$entry, 'activity']) }}" target="_blank" class="block text-sm font-medium text-sky-700 hover:text-sky-900 hover:underline">
                                                            {{ $entry->activity_file_original_name ?: 'PDF Kegiatan' }}
                                                        </a>
                                                    @endif
                                                    @if ($entry->receipt_file_path)
                                                        <a href="{{ route('perjadin.attachments.show', [$entry, 'receipt']) }}" target="_blank" class="block text-sm font-medium text-sky-700 hover:text-sky-900 hover:underline">
                                                            {{ $entry->receipt_file_original_name ?: 'PDF Nota / Tiket' }}
                                                        </a>
                                                    @endif
                                                    @if (! $entry->activity_file_path && ! $entry->receipt_file_path)
                                                        <span class="text-sm text-slate-400">Belum ada lampiran</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 align-top">
                                                <div class="flex min-w-52 flex-wrap gap-2">
                                                    <a href="{{ route('perjadin.show', ['perjadinEntry' => $entry, 'month' => $currentPeriod['month'], 'year' => $currentPeriod['year'], 'category' => $selectedCategory, 'keyword' => $selectedKeyword]) }}" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-sky-300 hover:text-sky-700">
                                                        Detail
                                                    </a>
                                                    <form action="{{ route('perjadin.payment.toggle', $entry) }}?month={{ $currentPeriod['month'] }}&year={{ $currentPeriod['year'] }}&category={{ urlencode($selectedCategory) }}&keyword={{ urlencode($selectedKeyword) }}" method="POST" onsubmit="return confirm('{{ $entry->paid_at ? 'Batalkan status pembayaran perjadin ini?' : 'Tandai perjadin ini sudah dibayar?' }}');">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center justify-center rounded-full border {{ $entry->paid_at ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100' : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }} px-3 py-1.5 text-xs font-semibold transition">
                                                            {{ $entry->paid_at ? 'Batalkan Bayar' : 'Bayar' }}
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('perjadin.duplicate', $entry) }}?month={{ $currentPeriod['month'] }}&year={{ $currentPeriod['year'] }}&category={{ urlencode($selectedCategory) }}&keyword={{ urlencode($selectedKeyword) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center justify-center rounded-full border border-emerald-200 bg-white px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50">
                                                            Duplikat
                                                        </button>
                                                    </form>
                                                    <a href="{{ route('perjadin.edit', ['perjadinEntry' => $entry, 'month' => $currentPeriod['month'], 'year' => $currentPeriod['year'], 'category' => $selectedCategory, 'keyword' => $selectedKeyword]) }}" class="inline-flex items-center justify-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 transition hover:bg-sky-100">
                                                        Edit
                                                    </a>
                                                    @if (auth()->user()->hasAnyRole(['admin', 'bendahara']))
                                                        <form action="{{ route('perjadin.destroy', $entry) }}?month={{ $currentPeriod['month'] }}&year={{ $currentPeriod['year'] }}&category={{ urlencode($selectedCategory) }}&keyword={{ urlencode($selectedKeyword) }}" method="POST" onsubmit="return confirm('Hapus data perjadin ini?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="inline-flex items-center justify-center rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">
                                                                Hapus
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            </div>
                            </div>
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
</x-layout>
