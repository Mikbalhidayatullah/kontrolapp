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
                        Kwitansi
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
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Lokasi TTD</p>
                        <p class="mt-2 font-semibold text-slate-900">{{ $entry->signature_location ?: '-' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 sm:col-span-2">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Kota / Kab Tujuan</p>
                        <p class="mt-2 font-semibold text-slate-900">{{ $entry->destination_city }}</p>
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
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <article class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Kegiatan</p>
                    @if ($entry->activity_file_path)
                        <a href="{{ route('perjadin.attachments.show', [$entry, 'activity']) }}" target="_blank" class="mt-3 inline-block font-semibold text-sky-700 hover:text-sky-900 hover:underline">
                            {{ $entry->activity_file_original_name ?: 'Lihat PDF kegiatan' }}
                        </a>
                    @else
                        <p class="mt-3 text-sm text-slate-400">Belum ada file kegiatan.</p>
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
            </div>
        </section>
        </div>
    </div>

    <dialog data-receipt-dialog class="w-full max-w-6xl rounded-[28px] border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-950/45">
        <div class="grid max-h-[88vh] overflow-y-auto lg:overflow-hidden lg:grid-cols-[0.95fr_1.05fr]">
            <div class="bg-white p-6 lg:max-h-[88vh] lg:overflow-y-auto lg:border-r lg:border-slate-200">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-emerald-600">Kwitansi Otomatis</p>
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

                    <div>
                        <label for="receipt_number" class="block text-sm font-medium text-slate-700">Nomor Kwitansi</label>
                        <input id="receipt_number" name="receipt_number" type="text" value="{{ $receiptDefaults['receipt_number'] }}" required data-receipt-input="receipt_number" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" />
                    </div>
                    <div>
                        <label for="received_from" class="block text-sm font-medium text-slate-700">Sudah terima dari</label>
                        <input id="received_from" name="received_from" type="text" value="{{ $receiptDefaults['received_from'] }}" data-receipt-input="received_from" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" />
                    </div>
                    <div>
                        <label for="payment_purpose" class="block text-sm font-medium text-slate-700">Untuk pembayaran</label>
                        <textarea id="payment_purpose" name="payment_purpose" rows="4" data-receipt-input="payment_purpose" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">{{ $receiptDefaults['payment_purpose'] }}</textarea>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="receipt_place" class="block text-sm font-medium text-slate-700">Tempat</label>
                            <input id="receipt_place" name="receipt_place" type="text" value="{{ $receiptDefaults['receipt_place'] }}" data-receipt-input="receipt_place" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" />
                        </div>
                        <div>
                            <label for="receipt_date" class="block text-sm font-medium text-slate-700">Tanggal Kwitansi</label>
                            <input id="receipt_date" name="receipt_date" type="date" value="{{ $receiptDefaults['receipt_date'] }}" data-receipt-input="receipt_date" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" />
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="recipient_name" class="block text-sm font-medium text-slate-700">Nama Penerima</label>
                            <input id="recipient_name" name="recipient_name" type="text" value="{{ $receiptDefaults['recipient_name'] }}" data-receipt-input="recipient_name" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" />
                        </div>
                        <div>
                            <label for="recipient_position" class="block text-sm font-medium text-slate-700">Jabatan Penerima</label>
                            <input id="recipient_position" name="recipient_position" type="text" value="{{ $receiptDefaults['recipient_position'] }}" data-receipt-input="recipient_position" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" />
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
                <div id="receipt-preview" class="mx-auto max-w-[780px] rounded-[24px] border border-slate-300 bg-white px-10 py-9 shadow-sm">
                    <div class="text-center text-[10px] font-bold uppercase tracking-[0.14em] text-slate-700">Dokumen Kwitansi Perjalanan Dinas</div>
                    <div class="mt-1 text-center text-[15px] font-bold uppercase tracking-[0.04em] text-slate-900">{{ strtoupper($entry->skpd_name) }}</div>
                    <div class="mt-1 text-center text-[10px] text-slate-500">Bukti penerimaan uang perjalanan dinas</div>

                    <div class="mt-4 flex items-start justify-between gap-6">
                        <div class="flex-1 text-center">
                            <p class="text-[2rem] font-bold tracking-[0.35em] text-slate-900">KWITANSI</p>
                            <p class="mt-2 text-[11px] uppercase tracking-[0.14em] text-slate-500">Bukti Penerimaan Uang</p>
                        </div>
                        <div class="w-[250px] shrink-0 border border-slate-900 px-4 py-3 text-center">
                            <p class="text-[11px] text-slate-500">No. Kwitansi</p>
                            <p class="mt-2 text-sm font-bold text-slate-900" data-receipt-preview="receipt_number">{{ $receiptDefaults['receipt_number'] ?: '-' }}</p>
                        </div>
                    </div>

                    <div class="mt-4 border-t-2 border-slate-900"></div>
                    <div class="mt-1 border-t border-slate-700"></div>

                    <div class="mt-6 space-y-3 text-sm leading-7 text-slate-700">
                        <div class="grid grid-cols-[165px_16px_minmax(0,1fr)] gap-2">
                            <span>Sudah terima dari</span>
                            <span>:</span>
                            <span data-receipt-preview="received_from">{{ $receiptDefaults['received_from'] }}</span>
                        </div>
                        <div class="grid grid-cols-[165px_16px_minmax(0,1fr)] gap-2">
                            <span>Untuk pembayaran</span>
                            <span>:</span>
                            <span data-receipt-preview="payment_purpose">{{ $receiptDefaults['payment_purpose'] }}</span>
                        </div>
                        <div class="grid grid-cols-[165px_16px_minmax(0,1fr)] gap-2">
                            <span>Kategori Perjadin</span>
                            <span>:</span>
                            <span>{{ $entry->category }}</span>
                        </div>
                        <div class="grid grid-cols-[165px_16px_minmax(0,1fr)] gap-2">
                            <span>Pelaksana</span>
                            <span>:</span>
                            <span>{{ $entry->executor_name }}</span>
                        </div>
                        <div class="grid grid-cols-[165px_16px_minmax(0,1fr)] gap-2">
                            <span>Tujuan</span>
                            <span>:</span>
                            <span>{{ $entry->destination_city }}</span>
                        </div>
                        <div class="grid grid-cols-[165px_16px_minmax(0,1fr)] gap-2">
                            <span>No Surat Tugas</span>
                            <span>:</span>
                            <span>{{ $entry->assignment_number }}</span>
                        </div>
                    </div>

                    <div class="mt-8 border-y border-slate-900 py-4">
                        <div class="grid grid-cols-[230px_minmax(0,1fr)] items-start gap-6">
                            <div>
                                <p class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Banyaknya Uang</p>
                                <p class="mt-2 text-[1.75rem] font-bold text-slate-900">Rp {{ number_format($entry->grand_total, 0, ',', '.') }}</p>
                            </div>
                            <div class="border border-slate-400 px-4 py-3 text-sm italic text-slate-600">
                                Terbilang: <span data-receipt-preview="grand_total_words"></span>
                            </div>
                        </div>
                    </div>

                    <p class="mt-8 text-sm leading-7 text-slate-700">
                        Telah diterima uang sejumlah tersebut di atas untuk kebutuhan perjalanan dinas sesuai rincian SPPD pada data perjadin yang tersimpan di sistem.
                    </p>

                    <div class="mt-24 grid grid-cols-2 gap-8 text-sm text-slate-700">
                        <div class="text-center">
                            <p class="font-semibold text-slate-900">Mengetahui,</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $entry->skpd_name }}</p>
                            <div class="pt-24">
                                <p class="font-semibold tracking-[0.22em] text-slate-700">........................................</p>
                                <p class="text-xs text-slate-500">Pejabat yang berwenang</p>
                            </div>
                        </div>
                        <div class="text-center">
                            <p><span data-receipt-preview="receipt_place">{{ $receiptDefaults['receipt_place'] }}</span>, <span data-receipt-preview="receipt_date_label"></span></p>
                            <p class="mt-1 text-xs text-slate-500">Yang menerima,</p>
                            <div class="pt-24">
                                <p class="font-semibold underline underline-offset-4 text-slate-900" data-receipt-preview="recipient_name">{{ $receiptDefaults['recipient_name'] }}</p>
                                <p data-receipt-preview="recipient_position">{{ $receiptDefaults['recipient_position'] }}</p>
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
                received_from: document.querySelector('[data-receipt-preview="received_from"]'),
                payment_purpose: document.querySelector('[data-receipt-preview="payment_purpose"]'),
                receipt_place: document.querySelector('[data-receipt-preview="receipt_place"]'),
                receipt_date_label: document.querySelector('[data-receipt-preview="receipt_date_label"]'),
                recipient_name: document.querySelector('[data-receipt-preview="recipient_name"]'),
                recipient_position: document.querySelector('[data-receipt-preview="recipient_position"]'),
                grand_total_words: document.querySelector('[data-receipt-preview="grand_total_words"]'),
                receipt_number: document.querySelector('[data-receipt-preview="receipt_number"]'),
            };

            const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            const staticReceipt = {
                skpdName: @json($entry->skpd_name),
                category: @json($entry->category),
                executorName: @json($entry->executor_name),
                destinationCity: @json($entry->destination_city),
                assignmentNumber: @json($entry->assignment_number),
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
                receivedFrom: document.getElementById('received_from')?.value || '-',
                paymentPurpose: document.getElementById('payment_purpose')?.value || '-',
                receiptPlace: document.getElementById('receipt_place')?.value || '-',
                receiptDate: formatLongDate(document.getElementById('receipt_date')?.value || ''),
                recipientName: document.getElementById('recipient_name')?.value || '-',
                recipientPosition: document.getElementById('recipient_position')?.value || '-',
                grandTotalLabel: `Rp ${new Intl.NumberFormat('id-ID').format(grandTotal)}`,
                grandTotalWords: terbilang(grandTotal).replace(/^./, (char) => char.toUpperCase()),
            });

            const buildReceiptPrintableHtml = (state) => `
                <div class="sheet">
                    <div class="office-name">Dokumen Kwitansi Perjalanan Dinas</div>
                    <div class="office-subname">${escapeHtml(staticReceipt.skpdName).toUpperCase()}</div>
                    <div class="office-caption">Bukti penerimaan uang perjalanan dinas</div>

                    <table class="header-table">
                        <tr>
                            <td class="title-cell">
                                <p class="title-main">KWITANSI</p>
                                <div class="title-sub">Bukti Penerimaan Uang</div>
                            </td>
                            <td style="width: 220px; text-align: right;">
                                <div class="number-box">
                                    <div>No. Kwitansi</div>
                                    <div style="margin-top: 4px; font-weight: bold;">${escapeHtml(state.receiptNumber)}</div>
                                </div>
                            </td>
                        </tr>
                    </table>

                    <div class="rule"></div>
                    <div class="rule-thin"></div>

                    <table class="meta">
                        <tr><td>Sudah terima dari</td><td>:</td><td>${escapeHtml(state.receivedFrom)}</td></tr>
                        <tr><td>Untuk pembayaran</td><td>:</td><td>${escapeHtml(state.paymentPurpose)}</td></tr>
                        <tr><td>Kategori Perjadin</td><td>:</td><td>${escapeHtml(staticReceipt.category)}</td></tr>
                        <tr><td>Nama Pelaksana</td><td>:</td><td>${escapeHtml(staticReceipt.executorName)}</td></tr>
                        <tr><td>Tujuan</td><td>:</td><td>${escapeHtml(staticReceipt.destinationCity)}</td></tr>
                        <tr><td>No. Surat Tugas</td><td>:</td><td>${escapeHtml(staticReceipt.assignmentNumber)}</td></tr>
                    </table>

                    <div class="amount-band">
                        <table class="amount-table">
                            <tr>
                                <td style="width: 220px;">
                                    <div class="amount-label">Banyaknya Uang</div>
                                    <div class="amount-main">${escapeHtml(state.grandTotalLabel)}</div>
                                </td>
                                <td>
                                    <div class="terbilang-box">Terbilang: ${escapeHtml(state.grandTotalWords)}</div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="body-copy">
                        Telah diterima uang sejumlah tersebut di atas untuk keperluan perjalanan dinas sesuai rincian SPPD
                        pada data perjadin yang tersimpan dalam sistem.
                    </div>

                    <table class="signature">
                        <tr>
                            <td>
                                <div class="signature-label">Mengetahui,</div>
                                <div class="signature-sub">${escapeHtml(staticReceipt.skpdName)}</div>
                                <div class="signature-space"></div>
                                <div class="manual-line">........................................</div>
                                <div class="signature-sub">Pejabat yang berwenang</div>
                            </td>
                            <td>
                                <div class="signature-label">${escapeHtml(state.receiptPlace)}, ${escapeHtml(state.receiptDate)}</div>
                                <div class="signature-sub">Yang menerima,</div>
                                <div class="signature-space"></div>
                                <div class="signature-name">${escapeHtml(state.recipientName)}</div>
                                <div class="signature-sub">${escapeHtml(state.recipientPosition)}</div>
                            </td>
                        </tr>
                    </table>
                </div>
            `;

            const receiptPrintStyles = `
                @page { size: A4 portrait; margin: 18mm 18mm 18mm 18mm; }
                * { box-sizing: border-box; }
                html, body { margin: 0; padding: 0; background: #ffffff; }
                body { font-family: "DejaVu Sans", Arial, sans-serif; color: #0f172a; font-size: 10.5px; line-height: 1.6; }
                .sheet { min-height: 255mm; width: 100%; max-width: 174mm; margin: 0 auto; overflow: hidden; }
                .office-name { text-align: center; font-size: 10px; font-weight: bold; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 2px; }
                .office-subname { text-align: center; font-size: 13px; font-weight: bold; text-transform: uppercase; margin-bottom: 3px; }
                .office-caption { text-align: center; font-size: 10px; color: #475569; margin-bottom: 10px; }
                .header-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 16px; }
                .header-table td { vertical-align: top; }
                .title-cell { width: 100%; text-align: center; }
                .title-main { font-size: 22px; font-weight: bold; letter-spacing: 4px; margin: 0; }
                .title-sub { font-size: 10px; margin-top: 6px; letter-spacing: 0.12em; text-transform: uppercase; }
                .number-box { width: 185px; max-width: 100%; margin-left: auto; border: 1px solid #0f172a; padding: 8px 10px; font-size: 10px; text-align: center; }
                .rule { border-top: 2px solid #0f172a; margin: 10px 0 16px; }
                .rule-thin { border-top: 1px solid #334155; margin: -12px 0 18px; }
                .meta { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 14px; }
                .meta td { vertical-align: top; padding: 3px 0; overflow-wrap: anywhere; word-break: break-word; }
                .meta td:first-child { width: 135px; }
                .meta td:nth-child(2) { width: 16px; }
                .amount-band { margin: 18px 0 12px; border-top: 1.3px solid #0f172a; border-bottom: 1.3px solid #0f172a; padding: 12px 0; }
                .amount-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
                .amount-table td { vertical-align: top; overflow-wrap: anywhere; word-break: break-word; }
                .amount-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: #475569; }
                .amount-main { font-size: 20px; font-weight: bold; margin-top: 4px; }
                .terbilang-box { border: 1px solid #94a3b8; padding: 10px 12px; margin-top: 12px; font-style: italic; overflow-wrap: anywhere; word-break: break-word; }
                .body-copy { margin-top: 18px; text-align: justify; }
                .signature { width: 100%; border-collapse: collapse; margin-top: 70px; }
                .signature td { width: 50%; vertical-align: top; text-align: center; }
                .signature-label { font-weight: bold; margin-bottom: 2px; }
                .signature-sub { font-size: 10px; color: #475569; }
                .signature-space { height: 78px; }
                .signature-name { font-weight: bold; text-decoration: underline; }
                .manual-line { margin: 0 auto 6px; width: 180px; text-align: center; letter-spacing: 0.16em; color: #334155; }
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

                    previewTargets[key].textContent = input.value || '-';
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
                            <title>Kwitansi Perjadin</title>
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
