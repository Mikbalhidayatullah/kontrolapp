<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    <div class="space-y-6">
        <section class="grid gap-4 lg:grid-cols-4">
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Total Data</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $summary['count'] }}</p>
                <p class="mt-2 text-sm text-slate-500">Data pajak pada filter aktif</p>
            </article>
            <article class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                <p class="text-sm font-medium text-emerald-700">Penerimaan</p>
                <p class="mt-3 text-2xl font-semibold text-slate-900">Rp {{ number_format($summary['receiptTotal'], 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">Total nominal penerimaan</p>
            </article>
            <article class="rounded-3xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                <p class="text-sm font-medium text-rose-700">Pengeluaran</p>
                <p class="mt-3 text-2xl font-semibold text-slate-900">Rp {{ number_format($summary['expenseTotal'], 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">Total nominal pengeluaran</p>
            </article>
            <article class="rounded-3xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
                <p class="text-sm font-medium text-sky-700">Saldo</p>
                <p class="mt-3 text-2xl font-semibold text-slate-900">Rp {{ number_format($summary['balanceTotal'], 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">Akumulasi saldo filter aktif</p>
            </article>
        </section>

        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-medium text-sky-600">Daftar Pajak</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Data pembayaran dan pencatatan pajak</h2>
                    <p class="mt-2 text-sm text-slate-500">Kelola periode GU, ID Billing, NTPN, rekening pajak, dan nominal transaksi.</p>
                </div>

                <div class="flex flex-col gap-3">
                    <form action="{{ route('pajak.index') }}" method="GET" data-auto-submit-filter class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <select name="category" data-auto-submit-control class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                            <option value="">Semua periode</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}" @selected($selectedCategory === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="keyword" value="{{ $selectedKeyword }}" placeholder="Cari bukti, billing, NTPN..." data-auto-submit-control data-auto-submit-delay="450" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100 sm:w-72" />
                        @if ($selectedCategory !== '' || $selectedKeyword !== '')
                            <a href="{{ route('pajak.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-500 transition hover:border-slate-300 hover:text-slate-700">
                                Reset
                            </a>
                        @endif
                    </form>

                    <div class="flex flex-wrap justify-end gap-3">
                        <a href="{{ route('pajak.export.xlsx') }}" class="inline-flex items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                            Download Excel
                        </a>
                        <button type="button" data-open-tax-print class="inline-flex items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                            Print
                        </button>
                        <a href="{{ route('pajak.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                            Tambah Pajak
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
            @if ($entries->isEmpty())
                <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                    Belum ada data pajak pada filter ini.
                </div>
            @else
                <div class="overflow-hidden rounded-3xl border border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-950 text-left text-slate-200">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Tanggal</th>
                                    <th class="px-4 py-3 font-medium">Kategori</th>
                                    <th class="px-4 py-3 font-medium">Nomor Bukti</th>
                                    <th class="px-4 py-3 font-medium">Uraian</th>
                                    <th class="px-4 py-3 font-medium">Rekening</th>
                                    <th class="px-4 py-3 font-medium">Billing / NTPN</th>
                                    <th class="px-4 py-3 text-right font-medium">Penerimaan</th>
                                    <th class="px-4 py-3 text-right font-medium">Pengeluaran</th>
                                    <th class="px-4 py-3 text-right font-medium">Saldo</th>
                                    <th class="px-4 py-3 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($entries as $entry)
                                    <tr>
                                        <td class="px-4 py-4 align-top text-slate-600">{{ optional($entry->entry_date)->translatedFormat('d M Y') }}</td>
                                        <td class="px-4 py-4 align-top">
                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">{{ $entry->category }}</span>
                                        </td>
                                        <td class="px-4 py-4 align-top font-medium text-slate-900">{{ $entry->proof_number }}</td>
                                        <td class="max-w-xs px-4 py-4 align-top text-slate-600">{{ $entry->description }}</td>
                                        <td class="px-4 py-4 align-top text-slate-600">
                                            <p class="font-medium text-slate-900">{{ $entry->account_name }}</p>
                                            <p class="mt-1 text-xs text-slate-400">{{ $entry->account_code ?: '-' }}</p>
                                        </td>
                                        <td class="px-4 py-4 align-top text-slate-600">
                                            <p>ID Billing: <span class="font-medium text-slate-900">{{ $entry->billing_id ?: '-' }}</span></p>
                                            <p class="mt-1">NTPN: <span class="font-medium text-slate-900">{{ $entry->ntpn ?: '-' }}</span></p>
                                        </td>
                                        <td class="px-4 py-4 text-right align-top font-medium text-emerald-700">Rp {{ number_format($entry->receipt_amount, 0, ',', '.') }}</td>
                                        <td class="px-4 py-4 text-right align-top font-medium text-rose-700">Rp {{ number_format($entry->expense_amount, 0, ',', '.') }}</td>
                                        <td class="px-4 py-4 text-right align-top font-semibold text-slate-900">Rp {{ number_format($entry->balance_amount, 0, ',', '.') }}</td>
                                        <td class="px-4 py-4 align-top">
                                            <div class="flex flex-wrap gap-2">
                                                <a href="{{ route('pajak.edit', $entry) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100" title="Edit data" aria-label="Edit data">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true">
                                                        <path d="M4 20h4l10.5-10.5a2.121 2.121 0 0 0-3-3L5 17v3Z" stroke-linecap="round" stroke-linejoin="round" />
                                                        <path d="m13.5 6.5 3 3" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </a>
                                                <form action="{{ route('pajak.destroy', $entry) }}" method="POST" onsubmit="return confirm('Hapus data pajak ini?');">
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
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </section>
    </div>

    <dialog data-tax-print-dialog class="w-full max-w-5xl rounded-[28px] border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/50">
        <div class="grid max-h-[92vh] overflow-hidden lg:grid-cols-[360px_minmax(0,1fr)]">
            <form method="dialog" class="space-y-5 overflow-y-auto border-b border-slate-200 p-6 lg:border-b-0 lg:border-r">
                <div>
                    <p class="text-sm font-medium text-emerald-600">Print Pajak</p>
                    <h3 class="mt-1 text-xl font-semibold text-slate-900">Tanda Bukti Pembayaran</h3>
                    <p class="mt-2 text-sm text-slate-500">Pilih periode GU dan isi tanda tangan sebelum print.</p>
                </div>
                <div>
                    <label for="print_category" class="block text-sm font-medium text-slate-700">Periode / GU</label>
                    <select id="print_category" data-tax-print-input="category" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100">
                        <option value="">Pilih periode</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected($selectedCategory === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="print_date" class="block text-sm font-medium text-slate-700">Tanggal</label>
                    <input id="print_date" type="date" value="{{ now()->format('Y-m-d') }}" data-tax-print-input="date" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" />
                </div>
                <div>
                    <label for="print_left_name" class="block text-sm font-medium text-slate-700">Yang Menyerahkan Kiri</label>
                    <input id="print_left_name" type="text" data-tax-print-input="left_name" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" />
                </div>
                <div>
                    <label for="print_right_name" class="block text-sm font-medium text-slate-700">Yang Menyerahkan Kanan</label>
                    <input id="print_right_name" type="text" data-tax-print-input="right_name" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" />
                </div>
                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="button" data-print-tax-proof class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Print
                    </button>
                    <button type="button" data-close-tax-print class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Tutup
                    </button>
                </div>
            </form>
            <div class="bg-slate-100 p-6">
                <div data-tax-print-preview class="mx-auto min-h-[520px] max-w-[680px] rounded-[24px] border border-slate-300 bg-white px-8 py-8 shadow-sm">
                    <h2 class="text-center text-xl font-bold text-slate-900">TANDA BUKTI PEMBAYARAN</h2>
                    <p class="hidden" data-tax-print-preview-category>Pilih periode</p>
                    <table class="mt-5 w-full border-collapse text-sm">
                        <thead>
                            <tr>
                                <th class="w-14 border border-slate-900 px-3 py-2 text-center text-base">NO</th>
                                <th class="border border-slate-900 px-3 py-2 text-center text-base">KODE EBILLING</th>
                                <th class="w-56 border border-slate-900 px-3 py-2 text-center text-base">JUMLAH</th>
                            </tr>
                        </thead>
                        <tbody data-tax-print-preview-rows></tbody>
                    </table>
                    <div class="mt-8 text-right text-lg text-slate-900" data-tax-print-preview-date></div>
                    <div class="mt-7 grid grid-cols-2 text-center text-lg text-slate-900">
                        <div>
                            <p>Yang Menyerahkan</p>
                            <div class="pt-16 font-medium" data-tax-print-preview-left>............................</div>
                        </div>
                        <div>
                            <p>Yang Menyerahkan</p>
                            <div class="pt-16 font-medium" data-tax-print-preview-right>............................</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </dialog>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const entries = @json($printEntries);

            const moneyLabel = (value) => Number(value || 0).toLocaleString('id-ID');
            const dateLabel = (value) => {
                if (!value) {
                    return '';
                }

                const formatted = new Intl.DateTimeFormat('id-ID', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric',
                }).format(new Date(`${value}T00:00:00`)).toUpperCase();

                return `Sofifi, ${formatted}`;
            };
            const escapeHtml = (value) => (value || '').toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const dialog = document.querySelector('[data-tax-print-dialog]');
            const openButton = document.querySelector('[data-open-tax-print]');
            const closeButton = document.querySelector('[data-close-tax-print]');
            const printButton = document.querySelector('[data-print-tax-proof]');
            const categoryInput = document.querySelector('[data-tax-print-input="category"]');
            const dateInput = document.querySelector('[data-tax-print-input="date"]');
            const leftInput = document.querySelector('[data-tax-print-input="left_name"]');
            const rightInput = document.querySelector('[data-tax-print-input="right_name"]');
            const preview = document.querySelector('[data-tax-print-preview]');
            const previewRows = document.querySelector('[data-tax-print-preview-rows]');
            const previewCategory = document.querySelector('[data-tax-print-preview-category]');
            const previewDate = document.querySelector('[data-tax-print-preview-date]');
            const previewLeft = document.querySelector('[data-tax-print-preview-left]');
            const previewRight = document.querySelector('[data-tax-print-preview-right]');

            const syncPreview = () => {
                const selectedCategory = categoryInput?.value || '';
                const filteredEntries = selectedCategory
                    ? entries.filter((entry) => entry.category === selectedCategory)
                    : [];
                const total = filteredEntries.reduce((sum, entry) => sum + Number(entry.amount || 0), 0);

                previewCategory.textContent = selectedCategory ? `BUKTI BAYAR ${selectedCategory}` : 'Pilih periode';
                previewRows.innerHTML = filteredEntries.length
                    ? filteredEntries.map((entry, index) => `
                        <tr>
                            <td class="border border-slate-900 px-3 py-1.5 text-center">${index + 1}</td>
                            <td class="border border-slate-900 px-3 py-1.5 text-center">${escapeHtml(entry.billing_id)}</td>
                            <td class="border border-slate-900 px-3 py-1.5">
                                <span class="inline-block w-8">Rp</span>
                                <span class="float-right">${moneyLabel(entry.amount)}</span>
                            </td>
                        </tr>
                    `).join('') + `
                        <tr>
                            <td colspan="2" class="border border-slate-900 px-3 py-1.5 text-center text-base font-bold tracking-[0.42em]">JUMLAH</td>
                            <td class="border border-slate-900 px-3 py-1.5 text-base font-bold">
                                <span class="inline-block w-8 font-bold">Rp</span>
                                <span class="float-right font-bold">${moneyLabel(total)}</span>
                            </td>
                        </tr>
                    `
                    : '<tr><td colspan="3" class="border border-slate-900 px-3 py-8 text-center text-slate-500">Pilih periode untuk menampilkan data.</td></tr>';
                previewDate.textContent = dateLabel(dateInput?.value);
                previewLeft.textContent = leftInput?.value || '............................';
                previewRight.textContent = rightInput?.value || '............................';
            };

            [categoryInput, dateInput, leftInput, rightInput].forEach((input) => {
                input?.addEventListener('input', syncPreview);
                input?.addEventListener('change', syncPreview);
            });

            openButton?.addEventListener('click', () => {
                if (categoryInput && !categoryInput.value && categoryInput.options.length > 1) {
                    categoryInput.selectedIndex = 1;
                }

                syncPreview();
                dialog?.showModal();
            });
            closeButton?.addEventListener('click', () => dialog?.close());
            printButton?.addEventListener('click', () => {
                syncPreview();
                const printWindow = window.open('', '_blank', 'width=900,height=700');
                if (!printWindow) {
                    return;
                }

                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html lang="id">
                    <head>
                        <meta charset="UTF-8">
                        <title>Tanda Bukti Pembayaran</title>
                        <style>
                            @page { size: A4 portrait; margin: 18mm; }
                            * { box-sizing: border-box; }
                            body { font-family: Arial, sans-serif; color: #000; font-size: 18px; }
                            .sheet { width: 100%; }
                            h2 { text-align: center; font-size: 20px; margin: 0; }
                            p { margin: 0; }
                            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                            th, td { border: 1px solid #000; padding: 5px 8px; }
                            th { text-align: center; font-size: 18px; }
                            th:first-child, td:first-child { width: 50px; text-align: center; }
                            th:last-child, td:last-child { width: 220px; }
                            td:nth-child(2) { text-align: center; }
                            td:last-child span:first-child { display: inline-block; width: 36px; }
                            td:last-child span:last-child { float: right; }
                            [data-tax-print-preview-category] { display: none; }
                            [data-tax-print-preview-date] { margin-top: 30px; text-align: right; font-size: 18px; }
                            .sheet > div:last-child { display: grid; grid-template-columns: 1fr 1fr; margin-top: 28px; text-align: center; font-size: 18px; }
                            .sheet > div:last-child div div { padding-top: 70px; font-weight: 500; }
                        </style>
                    </head>
                    <body><div class="sheet">${preview.innerHTML}</div></body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.focus();
                printWindow.print();
            });

            syncPreview();
        });
    </script>
</x-layout>
