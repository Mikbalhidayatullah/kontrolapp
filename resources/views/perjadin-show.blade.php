<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    <style>
        [data-print-only] {
            display: none;
        }

        @media print {
            nav,
            header,
            footer,
            [data-open-receipt],
            [data-print-detail],
            [data-receipt-dialog],
            [data-no-print] {
                display: none !important;
            }

            body,
            html {
                background: #fff !important;
            }

            main,
            main > div,
            [data-print-body] {
                margin: 0 !important;
                padding: 0 !important;
                max-width: none !important;
            }

            [data-print-section] {
                break-inside: avoid;
                box-shadow: none !important;
                border: 1px solid #cbd5e1 !important;
                page-break-inside: avoid;
            }

            [data-print-only] {
                display: block !important;
            }
        }
    </style>

    <div class="space-y-6">
        <section data-no-print class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-amber-600">{{ $entry->category }}</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ $entry->executor_name }}</h2>
                    <p class="mt-2 text-sm text-slate-500">{{ $periodLabel }}</p>
                    <div class="mt-3 space-y-1 text-xs text-slate-400">
                        <p>Ditambahkan oleh {{ $entry->creator?->name ?? 'Akun tidak diketahui' }}</p>
                        @if ($entry->updated_by && $entry->updater)
                            <p>Terakhir diedit oleh {{ $entry->updater->name }} pada {{ optional($entry->updated_at)->translatedFormat('d M Y H:i') }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('perjadin', ['month' => $currentPeriod['month'], 'year' => $currentPeriod['year'], 'category' => $activeCategory, 'keyword' => $activeKeyword]) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700">
                        Kembali ke Perjadin
                    </a>
                    <form action="{{ route('perjadin.duplicate', $entry) }}?month={{ $currentPeriod['month'] }}&year={{ $currentPeriod['year'] }}&category={{ urlencode($activeCategory) }}&keyword={{ urlencode($activeKeyword) }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                            Duplikat
                        </button>
                    </form>
                    <button type="button" data-open-receipt class="inline-flex items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                        Kuitansi
                    </button>
                    <a href="{{ route('perjadin.detail.pdf', ['perjadinEntry' => $entry]) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700">
                        Download Detail PDF
                    </a>
                    <button type="button" data-print-detail class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                        Cetak Detail
                    </button>
                    <a href="{{ route('perjadin.edit', ['perjadinEntry' => $entry, 'month' => $currentPeriod['month'], 'year' => $currentPeriod['year'], 'category' => $activeCategory, 'keyword' => $activeKeyword]) }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700">
                        Edit Data
                    </a>
                </div>
            </div>

            <div class="mt-6 grid gap-4 lg:grid-cols-4">
                <article class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                    <p class="text-sm font-medium text-slate-500">Grand Total</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">Rp {{ number_format($entry->grand_total, 0, ',', '.') }}</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                    <p class="text-sm font-medium text-slate-500">No Surat Tugas</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ $entry->assignment_number }}</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                    <p class="text-sm font-medium text-slate-500">Periode</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ optional($entry->start_date)->translatedFormat('d M Y') }} - {{ optional($entry->end_date)->translatedFormat('d M Y') }}</p>
                </article>
                <article class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                    <p class="text-sm font-medium text-slate-500">Tujuan</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ $entry->destination_city }}</p>
                </article>
            </div>
        </section>

        <div data-print-body class="space-y-6">
        <section data-print-only class="rounded-[24px] border border-slate-300 bg-white px-5 py-4">
            <div class="flex items-start justify-between gap-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Kategori</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $entry->category }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Nama Pelaksana</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $entry->executor_name }}</p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Grand Total</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">Rp {{ number_format($entry->grand_total, 0, ',', '.') }}</p>
                </div>
            </div>
        </section>
        <section class="grid gap-6 xl:grid-cols-2">
            <article data-print-section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-sky-600">01 Informasi Umum</p>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Nama SKPD</p>
                        <p class="mt-2 font-semibold text-slate-900">{{ $entry->skpd_name }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Nama Pelaksana</p>
                        <p class="mt-2 font-semibold text-slate-900">{{ $entry->executor_name }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Jabatan</p>
                        <p class="mt-2 font-semibold text-slate-900">{{ $entry->position_name }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Eselon</p>
                        <p class="mt-2 font-semibold text-slate-900">{{ $entry->echelon_level ?: '-' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Golongan</p>
                        <p class="mt-2 font-semibold text-slate-900">{{ $entry->grade }}</p>
                    </div>
                </div>
            </article>

            <article data-print-section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-sky-600">02 Jangka Waktu Surat Perintah Tugas</p>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Dari</p>
                        <p class="mt-2 font-semibold text-slate-900">{{ optional($entry->start_date)->translatedFormat('d M Y') }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Sampai</p>
                        <p class="mt-2 font-semibold text-slate-900">{{ optional($entry->end_date)->translatedFormat('d M Y') }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">No Surat Tugas</p>
                        <p class="mt-2 font-semibold text-slate-900">{{ $entry->assignment_number }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Tanggal Surat Tugas</p>
                        <p class="mt-2 font-semibold text-slate-900">{{ optional($entry->assignment_date)->translatedFormat('d M Y') }}</p>
                    </div>
                    @if ($entry->category === 'Perjadin Dalam Daerah')
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Kabupaten Asal</p>
                            <p class="mt-2 font-semibold text-slate-900">{{ $entry->origin_regency ?: '-' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Kecamatan Asal</p>
                            <p class="mt-2 font-semibold text-slate-900">{{ $entry->origin_district ?: '-' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Kabupaten Tujuan</p>
                            <p class="mt-2 font-semibold text-slate-900">{{ $entry->destination_regency ?: '-' }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Kecamatan Tujuan</p>
                            <p class="mt-2 font-semibold text-slate-900">{{ $entry->destination_district ?: '-' }}</p>
                        </div>
                    @else
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:col-span-2">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Kota / Kab Tujuan</p>
                            <p class="mt-2 font-semibold text-slate-900">{{ $entry->destination_city }}</p>
                        </div>
                    @endif
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:col-span-2">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Lokasi TTD</p>
                        <p class="mt-2 font-semibold text-slate-900">{{ $entry->signature_location ?: '-' }}</p>
                    </div>
                </div>
            </article>
        </section>

        <section data-print-section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-sky-600">03 Bukti Sesuai SPPD</p>
            <div class="mt-5 grid gap-5 xl:grid-cols-2">
                @foreach ($costGroups as $group)
                    <article class="rounded-3xl border {{ $group['enabled'] ? 'border-slate-200 bg-slate-50/70' : 'border-dashed border-slate-200 bg-white' }} p-5">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-base font-semibold text-slate-900">{{ $group['title'] }}</h3>
                            <span class="rounded-full px-3 py-1 text-xs font-medium {{ $group['enabled'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $group['enabled'] ? 'Aktif' : 'Tidak digunakan' }}
                            </span>
                        </div>

                        @if ($group['enabled'])
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                @foreach ($group['rows'] as $row)
                                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">{{ $row['label'] }}</p>
                                        <p class="mt-2 font-semibold text-slate-900">{{ $row['value'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-4 text-sm text-slate-400">Tidak ada input untuk bagian ini.</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>

        <section data-print-section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-sky-600">04 Dokumentasi</p>
            <div class="mt-5 grid gap-4 md:grid-cols-3">
                <article class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Foto Kegiatan</p>
                    @if ($entry->activity_file_path)
                        <a href="{{ route('perjadin.attachments.show', [$entry, 'activity']) }}" target="_blank" class="mt-3 inline-block font-semibold text-sky-700 hover:text-sky-900 hover:underline">
                            {{ $entry->activity_file_original_name ?: 'Lihat PDF foto kegiatan' }}
                        </a>
                    @else
                        <p class="mt-3 text-sm text-slate-400">Belum ada file foto kegiatan.</p>
                    @endif
                </article>
                <article class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Bukti Nota / Tiket</p>
                    @if ($entry->receipt_file_path)
                        <a href="{{ route('perjadin.attachments.show', [$entry, 'receipt']) }}" target="_blank" class="mt-3 inline-block font-semibold text-sky-700 hover:text-sky-900 hover:underline">
                            {{ $entry->receipt_file_original_name ?: 'Lihat PDF nota / tiket' }}
                        </a>
                    @else
                        <p class="mt-3 text-sm text-slate-400">Belum ada file nota / tiket.</p>
                    @endif
                </article>
                <article class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Laporan Perjadin</p>
                    @if ($entry->report_file_path)
                        <a href="{{ route('perjadin.attachments.show', [$entry, 'report']) }}" target="_blank" class="mt-3 inline-block font-semibold text-sky-700 hover:text-sky-900 hover:underline">
                            {{ $entry->report_file_original_name ?: 'Lihat PDF laporan perjadin' }}
                        </a>
                    @else
                        <p class="mt-3 text-sm text-slate-400">Belum ada file laporan perjadin.</p>
                    @endif
                </article>
            </div>
        </section>
        </div>
    </div>

    <dialog data-receipt-dialog class="w-full max-w-6xl rounded-[28px] border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-950/45">
        <div class="grid max-h-[88vh] overflow-y-auto lg:overflow-hidden lg:grid-cols-[0.95fr_1.05fr]">
            <div class="bg-white p-6 lg:max-h-[88vh] lg:overflow-y-auto lg:border-r lg:border-slate-200">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-emerald-600">Kuitansi Otomatis</p>
                        <h2 class="mt-1 text-xl font-semibold text-slate-900">Isi teks lalu lihat preview</h2>
                    </div>
                    <button type="button" data-close-receipt class="rounded-full border border-slate-200 px-3 py-1 text-sm text-slate-500 transition hover:text-slate-700">Tutup</button>
                </div>

                <form id="receipt-download-form" action="{{ route('perjadin.receipt.pdf', $entry) }}" method="POST" class="mt-6 space-y-4">
                    @csrf
                    <input type="hidden" name="month" value="{{ $currentPeriod['month'] }}">
                    <input type="hidden" name="year" value="{{ $currentPeriod['year'] }}">
                    <input type="hidden" name="category" value="{{ $activeCategory }}">
                    <input type="hidden" name="keyword" value="{{ $activeKeyword }}">

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="receipt_number" class="block text-sm font-medium text-slate-700">No. Kuitansi</label>
                            <input id="receipt_number" name="receipt_number" type="text" value="{{ $receiptDefaults['receipt_number'] }}" required data-receipt-input="receipt_number" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" />
                        </div>
                        <div>
                            <label for="budget_year" class="block text-sm font-medium text-slate-700">Tahun Anggaran</label>
                            <select id="budget_year" name="budget_year" data-receipt-input="budget_year" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                                @foreach ($receiptYearOptions as $yearOption)
                                    <option value="{{ $yearOption }}" @selected((int) $receiptDefaults['budget_year'] === (int) $yearOption)>{{ $yearOption }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="sheet_title" class="block text-sm font-medium text-slate-700">Lembaran</label>
                        <input id="sheet_title" name="sheet_title" type="text" value="{{ $receiptDefaults['sheet_title'] }}" data-receipt-input="sheet_title" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" />
                    </div>
                    <div>
                        <label for="received_from" class="block text-sm font-medium text-slate-700">Sudah terima dari</label>
                        <input id="received_from" name="received_from" type="text" value="{{ $receiptDefaults['received_from'] }}" data-receipt-input="received_from" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" />
                    </div>
                    <div>
                        <label for="payment_purpose" class="block text-sm font-medium text-slate-700">Untuk pengeluaran</label>
                        <textarea id="payment_purpose" name="payment_purpose" rows="4" data-receipt-input="payment_purpose" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">{{ $receiptDefaults['payment_purpose'] }}</textarea>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="receipt_place" class="block text-sm font-medium text-slate-700">Tempat</label>
                            <input id="receipt_place" name="receipt_place" type="text" value="{{ $receiptDefaults['receipt_place'] }}" data-receipt-input="receipt_place" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" />
                        </div>
                        <div>
                            <label for="receipt_date" class="block text-sm font-medium text-slate-700">Tanggal Kuitansi</label>
                            <input id="receipt_date" name="receipt_date" type="date" value="{{ $receiptDefaults['receipt_date'] }}" data-receipt-input="receipt_date" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" />
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="recipient_name" class="block text-sm font-medium text-slate-700">Nama Penerima</label>
                            <input id="recipient_name" name="recipient_name" type="text" value="{{ $receiptDefaults['recipient_name'] }}" data-receipt-input="recipient_name" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" />
                        </div>
                        <div>
                            <label for="recipient_nip" class="block text-sm font-medium text-slate-700">NIP Penerima</label>
                            <input id="recipient_nip" name="recipient_nip" type="text" value="{{ $receiptDefaults['recipient_nip'] }}" data-receipt-input="recipient_nip" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" />
                        </div>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-800">Setuju Dibayar</p>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="approver_name" class="block text-sm font-medium text-slate-700">Nama Kepala Badan</label>
                                <input id="approver_name" name="approver_name" type="text" value="{{ $receiptDefaults['approver_name'] }}" data-receipt-input="approver_name" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" />
                            </div>
                            <div>
                                <label for="approver_nip" class="block text-sm font-medium text-slate-700">NIP Kepala Badan</label>
                                <input id="approver_nip" name="approver_nip" type="text" value="{{ $receiptDefaults['approver_nip'] }}" data-receipt-input="approver_nip" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" />
                            </div>
                        </div>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-800">Lunas Dibayar</p>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="treasurer_name" class="block text-sm font-medium text-slate-700">Nama Bendahara Pengeluaran</label>
                                <input id="treasurer_name" name="treasurer_name" type="text" value="{{ $receiptDefaults['treasurer_name'] }}" data-receipt-input="treasurer_name" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" />
                            </div>
                            <div>
                                <label for="treasurer_nip" class="block text-sm font-medium text-slate-700">NIP Bendahara Pengeluaran</label>
                                <input id="treasurer_nip" name="treasurer_nip" type="text" value="{{ $receiptDefaults['treasurer_nip'] }}" data-receipt-input="treasurer_nip" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" />
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-4">
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            Download PDF
                        </button>
                        <button type="button" data-print-receipt class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                            Print
                        </button>
                    </div>
                </form>
            </div>

              <div class="bg-slate-100 p-6 lg:max-h-[88vh] lg:overflow-y-auto">
                  <div id="receipt-preview" class="mx-auto max-w-[780px] rounded-[24px] border border-slate-300 bg-white px-8 py-7 shadow-sm">
                      <div class="grid grid-cols-[78px_minmax(0,1fr)] items-center gap-3">
                          <div class="flex items-center justify-start">
                              <img src="{{ asset('images/logos/maluku_utara.png') }}" alt="Logo Provinsi Maluku Utara" class="h-[56px] w-[56px] object-contain" />
                          </div>
                          <div class="pr-8 text-center leading-tight">
                              <p class="text-[16px] font-bold uppercase tracking-[0.02em] text-slate-900">PEMERINTAH PROVINSI MALUKU UTARA</p>
                              <p class="text-[14px] font-bold uppercase text-slate-900">DINAS PENDIDIKAN DAN KEBUDAYAAN</p>
                              <p class="text-[13px] text-slate-700">Jln. Raya Sultan Nuku, Kota Tidore Kepulauan</p>
                              <p class="pl-[0.38em] text-[18px] font-bold tracking-[0.38em] text-slate-900">SOFIFI</p>
                          </div>
                      </div>

                    <div class="mt-2 h-[3px] border-t-2 border-b border-slate-900"></div>

                    <div class="mt-3 grid grid-cols-2 gap-4 text-[12px] leading-5 text-slate-800">
                        <div class="space-y-1">
                            <div class="grid grid-cols-[96px_14px_minmax(0,1fr)] gap-1">
                                <span>No. kuitansi</span>
                                <span>:</span>
                                <span data-receipt-preview="receipt_number">{{ $receiptDefaults['receipt_number'] ?: '-' }}</span>
                            </div>
                            <div class="grid grid-cols-[96px_14px_minmax(0,1fr)] gap-1">
                                <span>Lembaran</span>
                                <span>:</span>
                                <span data-receipt-preview="sheet_title">{{ $receiptDefaults['sheet_title'] ?: '-' }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-[126px_14px_minmax(0,1fr)] justify-self-end gap-1 text-right">
                            <span>Tahun Anggaran</span>
                            <span>:</span>
                            <span data-receipt-preview="budget_year">{{ $receiptDefaults['budget_year'] }}</span>
                        </div>
                    </div>

                    <div class="mt-6 text-center">
                        <p class="text-[22px] font-bold tracking-[0.08em] text-slate-900">KUITANSI</p>
                    </div>

                    <div class="mt-4 space-y-2 text-sm leading-6 text-slate-700">
                        <div class="grid grid-cols-[165px_16px_minmax(0,1fr)] gap-2">
                            <span>Sudah terima dari</span>
                            <span>:</span>
                            <span data-receipt-preview="received_from">{{ $receiptDefaults['received_from'] }}</span>
                        </div>
                        <div class="grid grid-cols-[165px_16px_minmax(0,1fr)] gap-2">
                            <span>Sebesar</span>
                            <span>:</span>
                            <span class="font-semibold text-slate-900">Rp {{ number_format($entry->grand_total, 0, ',', '.') }}</span>
                        </div>
                        <div class="grid grid-cols-[165px_16px_minmax(0,1fr)] gap-2">
                            <span>Terbilang</span>
                            <span>:</span>
                            <span class="italic text-slate-600" data-receipt-preview="grand_total_words"></span>
                        </div>
                        <div class="grid grid-cols-[165px_16px_minmax(0,1fr)] gap-2">
                            <span>Untuk pengeluaran</span>
                            <span>:</span>
                            <span data-receipt-preview="payment_purpose">{{ $receiptDefaults['payment_purpose'] }}</span>
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="grid grid-cols-[165px_16px_minmax(0,1fr)] gap-2 text-sm leading-6 text-slate-700">
                            <span></span>
                            <span></span>
                            <span>dengan rincian :</span>
                        </div>
                        <div class="mt-2 ml-[181px] overflow-hidden rounded-2xl">
                            <table class="min-w-full text-sm">
                                <thead class="text-left text-slate-700">
                                    <tr>
                                        <th class="w-12 px-3 py-2 font-semibold">No</th>
                                        <th class="px-3 py-2 font-semibold">Uraian</th>
                                        <th class="w-40 px-3 py-2 text-right font-semibold">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    @forelse ($receiptBreakdown as $index => $item)
                                        <tr>
                                            <td class="px-3 py-2 text-center text-slate-600">{{ $index + 1 }}</td>
                                            <td class="px-3 py-2 text-slate-700">{{ $item['description'] }}</td>
                                            <td class="px-3 py-2 text-right font-medium text-slate-900">{{ $item['total_label'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="px-3 py-2 text-center text-slate-600">1</td>
                                            <td class="px-3 py-2 text-slate-700">Biaya perjalanan dinas sesuai rincian SPPD</td>
                                            <td class="px-3 py-2 text-right font-medium text-slate-900">Rp {{ number_format($entry->grand_total, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforelse
                                    <tr class="bg-slate-50 font-semibold text-slate-900">
                                        <td colspan="2" class="px-3 py-2">Total Jumlah</td>
                                        <td class="px-3 py-2 text-right">Rp {{ number_format($entry->grand_total, 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-5 ml-auto w-[160px] text-center text-sm text-slate-700">
                        <span data-receipt-preview="receipt_place">{{ $receiptDefaults['receipt_place'] }}</span>, <span data-receipt-preview="receipt_date_label"></span>
                    </div>

                    <div class="mt-3 ml-auto w-[160px] text-center text-sm text-slate-700">
                        <p>Penerima,</p>
                        <p class="mt-7 text-slate-500">Materai 10rb</p>
                        <p class="mt-5 whitespace-nowrap font-semibold underline underline-offset-4 text-slate-900" data-receipt-preview="recipient_name">{{ $receiptDefaults['recipient_name'] }}</p>
                        <p class="mt-1" data-receipt-preview="recipient_nip">NIP. {{ $receiptDefaults['recipient_nip'] ?: '-' }}</p>
                    </div>

                    <div class="mt-12 grid grid-cols-2 text-sm text-slate-700">
                        <div class="w-[230px] justify-self-start text-center">
                            <p class="font-semibold text-slate-900">Mengetahui dan Menyetujui,</p>
                            <p class="mt-1">Kepala Dinas Pendidikan Dan Kebudayaan</p>
                            <p>Provinsi Maluku Utara</p>
                            <div class="pt-14">
                                <p class="whitespace-nowrap font-semibold underline underline-offset-4 text-slate-900" data-receipt-preview="approver_name">{{ $receiptDefaults['approver_name'] ?: '........................................' }}</p>
                                <p class="mt-1" data-receipt-preview="approver_nip">NIP. {{ $receiptDefaults['approver_nip'] ?: '........................................' }}</p>
                            </div>
                        </div>
                        <div class="w-[160px] justify-self-end text-center">
                            <p class="pt-8">Bendahara Pengeluaran</p>
                            <div class="pt-14">
                                <p class="whitespace-nowrap font-semibold underline underline-offset-4 text-slate-900" data-receipt-preview="treasurer_name">{{ $receiptDefaults['treasurer_name'] ?: '........................................' }}</p>
                                <p class="mt-1" data-receipt-preview="treasurer_nip">NIP. {{ $receiptDefaults['treasurer_nip'] ?: '........................................' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </dialog>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dialog = document.querySelector('[data-receipt-dialog]');
            const openButton = document.querySelector('[data-open-receipt]');
            const closeButton = document.querySelector('[data-close-receipt]');
            const printButton = document.querySelector('[data-print-receipt]');
            const printDetailButton = document.querySelector('[data-print-detail]');
            const preview = document.getElementById('receipt-preview');
            const inputs = document.querySelectorAll('[data-receipt-input]');
            const grandTotal = {{ (int) $entry->grand_total }};

              const previewTargets = {
                receipt_number: document.querySelector('[data-receipt-preview="receipt_number"]'),
                sheet_title: document.querySelector('[data-receipt-preview="sheet_title"]'),
                budget_year: document.querySelector('[data-receipt-preview="budget_year"]'),
                received_from: document.querySelector('[data-receipt-preview="received_from"]'),
                payment_purpose: document.querySelector('[data-receipt-preview="payment_purpose"]'),
                receipt_place: document.querySelector('[data-receipt-preview="receipt_place"]'),
                receipt_date_label: document.querySelector('[data-receipt-preview="receipt_date_label"]'),
                recipient_name: document.querySelector('[data-receipt-preview="recipient_name"]'),
                recipient_nip: document.querySelector('[data-receipt-preview="recipient_nip"]'),
                approver_name: document.querySelector('[data-receipt-preview="approver_name"]'),
                approver_nip: document.querySelector('[data-receipt-preview="approver_nip"]'),
                treasurer_name: document.querySelector('[data-receipt-preview="treasurer_name"]'),
                treasurer_nip: document.querySelector('[data-receipt-preview="treasurer_nip"]'),
                grand_total_words: document.querySelector('[data-receipt-preview="grand_total_words"]'),
              };
              const receiptLogoUrl = @json(asset('images/logos/maluku_utara.png'));

            const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const staticReceipt = {
                breakdown: @json($receiptBreakdown),
            };

            const formatLongDate = (value) => {
                if (!value) {
                    return '-';
                }

                const date = new Date(`${value}T00:00:00`);
                if (Number.isNaN(date.getTime())) {
                    return value;
                }

                return `${String(date.getDate()).padStart(2, '0')} ${monthNames[date.getMonth()]} ${date.getFullYear()}`;
            };

            const terbilang = (value) => {
                const words = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

                const toWords = (number) => {
                    number = Math.abs(number);

                    if (number < 12) return ` ${words[number]}`;
                    if (number < 20) return `${toWords(number - 10)} belas`;
                    if (number < 100) return `${toWords(Math.floor(number / 10))} puluh${toWords(number % 10)}`;
                    if (number < 200) return ` seratus${toWords(number - 100)}`;
                    if (number < 1000) return `${toWords(Math.floor(number / 100))} ratus${toWords(number % 100)}`;
                    if (number < 2000) return ` seribu${toWords(number - 1000)}`;
                    if (number < 1000000) return `${toWords(Math.floor(number / 1000))} ribu${toWords(number % 1000)}`;
                    if (number < 1000000000) return `${toWords(Math.floor(number / 1000000))} juta${toWords(number % 1000000)}`;
                    if (number < 1000000000000) return `${toWords(Math.floor(number / 1000000000))} miliar${toWords(number % 1000000000)}`;

                    return `${toWords(Math.floor(number / 1000000000000))} triliun${toWords(number % 1000000000000)}`;
                };

                return `${toWords(value).trim()} rupiah`;
            };

            const escapeHtml = (value) => {
                return (value || '').toString()
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };

            const receiptState = () => ({
                receiptNumber: document.getElementById('receipt_number')?.value || '-',
                sheetTitle: document.getElementById('sheet_title')?.value || '-',
                budgetYear: document.getElementById('budget_year')?.value || '{{ now()->year }}',
                receivedFrom: document.getElementById('received_from')?.value || '-',
                paymentPurpose: document.getElementById('payment_purpose')?.value || '-',
                receiptPlace: document.getElementById('receipt_place')?.value || '-',
                receiptDate: formatLongDate(document.getElementById('receipt_date')?.value || ''),
                recipientName: document.getElementById('recipient_name')?.value || '-',
                recipientNip: document.getElementById('recipient_nip')?.value || '-',
                approverName: document.getElementById('approver_name')?.value || '........................................',
                approverNip: document.getElementById('approver_nip')?.value || '........................................',
                treasurerName: document.getElementById('treasurer_name')?.value || '........................................',
                treasurerNip: document.getElementById('treasurer_nip')?.value || '........................................',
                grandTotalLabel: `Rp ${new Intl.NumberFormat('id-ID').format(grandTotal)}`,
                grandTotalWords: terbilang(grandTotal).replace(/^./, (char) => char.toUpperCase()),
            });

            const buildBreakdownRows = () => {
                if (!staticReceipt.breakdown.length) {
                    return `
                        <tr>
                            <td>1</td>
                            <td>Biaya perjalanan dinas sesuai rincian SPPD</td>
                            <td>${escapeHtml(`Rp ${new Intl.NumberFormat('id-ID').format(grandTotal)}`)}</td>
                        </tr>
                    `;
                }

                return staticReceipt.breakdown.map((item, index) => `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${escapeHtml(item.description)}</td>
                        <td>${escapeHtml(item.total_label)}</td>
                    </tr>
                `).join('');
            };

            const buildReceiptPrintableHtml = (state) => `
                  <div class="sheet">
                      <div class="kop">
                          <table class="kop-table">
                              <tr>
                                  <td class="kop-logo-cell">${receiptLogoUrl ? `<img src="${escapeHtml(receiptLogoUrl)}" alt="Logo Provinsi Maluku Utara" class="kop-logo">` : ''}</td>
                                  <td class="kop-text-cell">
                                      <div class="kop-line-1">PEMERINTAH PROVINSI MALUKU UTARA</div>
                                      <div class="kop-line-2">DINAS PENDIDIKAN DAN KEBUDAYAAN</div>
                                      <div class="kop-line-3">Jln. Raya Sultan Nuku, Kota Tidore Kepulauan</div>
                                      <div class="kop-line-4">SOFIFI</div>
                                  </td>
                              </tr>
                          </table>
                      </div>

                    <div class="divider"></div>

                    <table class="receipt-head-meta">
                        <tr>
                            <td class="receipt-head-left">
                                <table class="receipt-head-inner">
                                    <tr><td class="receipt-head-label">No. kuitansi</td><td class="receipt-head-colon">:</td><td>${escapeHtml(state.receiptNumber)}</td></tr>
                                    <tr><td class="receipt-head-label">Lembaran</td><td class="receipt-head-colon">:</td><td>${escapeHtml(state.sheetTitle)}</td></tr>
                                </table>
                            </td>
                            <td class="receipt-head-right">
                                <table class="receipt-head-inner receipt-head-inner-right">
                                    <tr><td class="receipt-head-label-year">Tahun Anggaran</td><td class="receipt-head-colon">:</td><td>${escapeHtml(state.budgetYear)}</td></tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <div class="title">KUITANSI</div>

                    <table class="meta-table">
                        <tr><td>Sudah terima dari</td><td>:</td><td>${escapeHtml(state.receivedFrom)}</td></tr>
                        <tr><td>Sebesar</td><td>:</td><td>${escapeHtml(state.grandTotalLabel)}</td></tr>
                        <tr><td>Terbilang</td><td>:</td><td><span class="meta-italic">${escapeHtml(state.grandTotalWords)}</span></td></tr>
                        <tr><td>Untuk pengeluaran</td><td>:</td><td>${escapeHtml(state.paymentPurpose)}</td></tr>
                    </table>

                    <div class="label-rincian"><span></span><span></span><span>dengan rincian :</span></div>
                    <div class="detail-table-wrap">
                        <table class="detail-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Uraian</th>
                                    <th>Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${buildBreakdownRows()}
                                <tr class="detail-total">
                                    <td colspan="2">Total Jumlah</td>
                                    <td>${escapeHtml(state.grandTotalLabel)}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="receipt-date">${escapeHtml(state.receiptPlace)}, ${escapeHtml(state.receiptDate)}</div>

                    <div class="recipient-block">
                        <div>Penerima,</div>
                        <div class="stamp">Materai 10rb</div>
                        <div class="signature-name">${escapeHtml(state.recipientName)}</div>
                        <div class="signature-nip">NIP. ${escapeHtml(state.recipientNip)}</div>
                    </div>

                    <table class="approval-grid">
                        <tr>
                            <td>
                                <div class="approval-block approval-block-left">
                                    <div class="approval-title">Mengetahui dan Menyetujui,</div>
                                    <div class="approval-subtitle">Kepala Dinas Pendidikan Dan Kebudayaan</div>
                                    <div>Provinsi Maluku Utara</div>
                                    <div class="approval-space"></div>
                                    <div class="approval-name">${escapeHtml(state.approverName)}</div>
                                    <div>NIP. ${escapeHtml(state.approverNip)}</div>
                                </div>
                            </td>
                            <td>
                                <div class="approval-block approval-block-right">
                                    <div class="approval-subtitle approval-subtitle-plain">Bendahara Pengeluaran</div>
                                    <div class="approval-space"></div>
                                    <div class="approval-name">${escapeHtml(state.treasurerName)}</div>
                                    <div>NIP. ${escapeHtml(state.treasurerNip)}</div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            `;

            const receiptPrintStyles = `
                @page { size: A4 portrait; margin: 16mm 18mm 18mm; }
                * { box-sizing: border-box; }
                html, body { margin: 0; padding: 0; background: #ffffff; }
                body { font-family: "DejaVu Sans", Arial, sans-serif; color: #111827; font-size: 11px; line-height: 1.38; }
                .sheet { min-height: 257mm; width: 100%; max-width: 174mm; margin: 0 auto; }
                  .kop { margin-bottom: 6px; }
                  .kop-table { width: 100%; border-collapse: collapse; }
                  .kop-logo-cell { width: 78px; vertical-align: middle; text-align: left; }
                  .kop-text-cell { vertical-align: middle; text-align: center; padding-right: 32px; }
                  .kop-logo { width: 56px; height: 56px; object-fit: contain; display: block; }
                  .kop-line-1 { font-size: 16px; font-weight: 700; letter-spacing: .02em; line-height: 1.05; }
                .kop-line-2 { font-size: 14px; font-weight: 700; line-height: 1.05; margin-top: 0; }
                .kop-line-3 { font-size: 13px; line-height: 1.05; margin-top: 0; }
                .kop-line-4 { font-size: 18px; font-weight: 700; letter-spacing: .38em; line-height: 1.05; margin-top: 1px; padding-left: .38em; }
                .divider { border-top: 2px solid #111827; border-bottom: 1px solid #111827; height: 2px; margin: 6px 0 12px; }
                .receipt-head-meta, .meta-table, .detail-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
                .receipt-head-meta { margin-bottom: 14px; font-size: 12px; }
                .receipt-head-left { width: 50%; vertical-align: top; }
                .receipt-head-right { width: 50%; vertical-align: top; text-align: right; }
                .receipt-head-inner { width: 100%; border-collapse: collapse; }
                .receipt-head-inner td { padding: 0; vertical-align: top; }
                .receipt-head-inner td:first-child { width: 96px; }
                .receipt-head-inner td:nth-child(2) { width: 14px; }
                .receipt-head-inner-right { margin-left: auto; width: 220px; }
                .receipt-head-inner-right td:first-child { width: 126px; }
                .receipt-head-label, .receipt-head-label-year, .receipt-head-colon { white-space: nowrap; }
                .title { text-align: center; font-size: 22px; font-weight: 700; letter-spacing: .12em; margin: 0 0 12px; }
                .meta-table td { padding: 2px 0; vertical-align: top; word-break: break-word; }
                .meta-table td:first-child { width: 130px; }
                .meta-table td:nth-child(2) { width: 14px; }
                .meta-italic { font-style: italic; }
                .label-rincian { display: grid; grid-template-columns: 130px 14px minmax(0,1fr); gap: 0; margin-bottom: 6px; font-weight: 400; }
                .detail-table th, .detail-table td { padding: 5px 7px; vertical-align: top; word-break: break-word; }
                .detail-table th { font-size: 10px; text-transform: uppercase; letter-spacing: .04em; text-align: left; }
                .detail-table td:last-child, .detail-table th:last-child { text-align: right; white-space: nowrap; }
                .detail-table td:first-child, .detail-table th:first-child { width: 34px; text-align: center; }
                .detail-total td { font-weight: 700; }
                .detail-table-wrap { margin-left: 144px; width: calc(100% - 144px); }
                .receipt-date { width: 160px; margin-top: 14px; margin-left: auto; margin-right: 0; text-align: center; }
                .recipient-block { width: 160px; margin-left: auto; margin-right: 0; margin-top: 6px; text-align: center; }
                .stamp { margin-top: 26px; font-size: 11px; }
                .signature-name { margin-top: 18px; font-weight: 700; text-decoration: underline; white-space: nowrap; }
                .signature-nip { margin-top: 4px; }
                .approval-grid { width: 100%; border-collapse: collapse; margin-top: 42px; }
                .approval-grid td { width: 50%; vertical-align: top; text-align: center; }
                .approval-block { text-align: center; }
                .approval-block-left { width: 230px; margin-left: 0; margin-right: auto; }
                .approval-block-right { width: 160px; margin-left: auto; margin-right: 0; }
                .approval-title { font-weight: 700; }
                .approval-subtitle { margin-top: 2px; }
                .approval-subtitle-plain { margin-top: 31px; }
                .approval-space { height: 54px; }
                .approval-name { font-weight: 700; text-decoration: underline; white-space: nowrap; }
            `;

            const syncPreview = () => {
                previewTargets.grand_total_words.textContent = terbilang(grandTotal).replace(/^./, (char) => char.toUpperCase());

                inputs.forEach((input) => {
                    const key = input.dataset.receiptInput;
                    if (!previewTargets[key] && key !== 'receipt_date') {
                        return;
                    }

                    if (key === 'receipt_date') {
                        previewTargets.receipt_date_label.textContent = formatLongDate(input.value);
                        return;
                    }

                    if (['recipient_nip', 'approver_nip', 'treasurer_nip'].includes(key)) {
                        previewTargets[key].textContent = `NIP. ${input.value || '........................................'}`;
                        return;
                    }

                    previewTargets[key].textContent = input.value || (['approver_name', 'treasurer_name'].includes(key) ? '........................................' : '-');
                });
            };

            openButton?.addEventListener('click', () => {
                dialog?.showModal();
                syncPreview();
            });

            closeButton?.addEventListener('click', () => {
                dialog?.close();
            });

            printButton?.addEventListener('click', () => {
                syncPreview();
                const state = receiptState();

                const printWindow = window.open('', '_blank', 'width=900,height=700');
                if (!printWindow) {
                    return;
                }

                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html lang="id">
                        <head>
                            <meta charset="UTF-8">
                            <title>Kuitansi Perjadin</title>
                            <style>${receiptPrintStyles}</style>
                        </head>
                        <body>
                            ${buildReceiptPrintableHtml(state)}
                        </body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.onload = () => {
                    printWindow.focus();
                    window.setTimeout(() => {
                        printWindow.print();
                        printWindow.onafterprint = () => printWindow.close();
                    }, 300);
                };
            });

            printDetailButton?.addEventListener('click', () => {
                window.print();
            });

            inputs.forEach((input) => {
                input.addEventListener('input', syncPreview);
                input.addEventListener('change', syncPreview);
            });

            syncPreview();
        });
    </script>
</x-layout>
