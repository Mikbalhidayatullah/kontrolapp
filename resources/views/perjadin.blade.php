<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    <div class="space-y-6">
        <section class="grid gap-4 lg:grid-cols-3">
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
                        <a href="{{ route('add-perjadin', ['month' => $currentPeriod['month'], 'year' => $currentPeriod['year'], 'category' => $selectedCategory, 'keyword' => $selectedKeyword]) }}" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                            Tambah Perjadin
                        </a>
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
                                                <div class="flex flex-wrap gap-2">
                                                    <a href="{{ route('perjadin.show', ['perjadinEntry' => $entry, 'month' => $currentPeriod['month'], 'year' => $currentPeriod['year'], 'category' => $selectedCategory, 'keyword' => $selectedKeyword]) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 transition hover:border-sky-300 hover:text-sky-700" title="Lihat detail" aria-label="Lihat detail">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true">
                                                            <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.269 2.943 9.542 7-1.273 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z" stroke-linecap="round" stroke-linejoin="round" />
                                                            <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </a>
                                                    <form action="{{ route('perjadin.duplicate', $entry) }}?month={{ $currentPeriod['month'] }}&year={{ $currentPeriod['year'] }}&category={{ urlencode($selectedCategory) }}&keyword={{ urlencode($selectedKeyword) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:bg-emerald-100" title="Duplikat data" aria-label="Duplikat data">
                                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true">
                                                                <rect x="9" y="9" width="10" height="10" rx="2" />
                                                                <path d="M5 15V7a2 2 0 0 1 2-2h8" stroke-linecap="round" stroke-linejoin="round" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                    <a href="{{ route('perjadin.edit', ['perjadinEntry' => $entry, 'month' => $currentPeriod['month'], 'year' => $currentPeriod['year'], 'category' => $selectedCategory, 'keyword' => $selectedKeyword]) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100" title="Edit data" aria-label="Edit data">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true">
                                                            <path d="M4 20h4l10.5-10.5a2.121 2.121 0 0 0-3-3L5 17v3Z" stroke-linecap="round" stroke-linejoin="round" />
                                                            <path d="m13.5 6.5 3 3" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </a>
                                                    @if (auth()->user()->hasAnyRole(['admin', 'bendahara']))
                                                        <form action="{{ route('perjadin.destroy', $entry) }}?month={{ $currentPeriod['month'] }}&year={{ $currentPeriod['year'] }}&category={{ urlencode($selectedCategory) }}&keyword={{ urlencode($selectedKeyword) }}" method="POST" onsubmit="return confirm('Hapus data perjadin ini?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100" title="Hapus data" aria-label="Hapus data">
                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true">
                                                                    <path d="M3 6h18" stroke-linecap="round" stroke-linejoin="round" />
                                                                    <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" stroke-linecap="round" stroke-linejoin="round" />
                                                                    <path d="M19 6l-1 14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1L5 6" stroke-linecap="round" stroke-linejoin="round" />
                                                                    <path d="M10 11v6M14 11v6" stroke-linecap="round" stroke-linejoin="round" />
                                                                </svg>
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

