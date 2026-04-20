<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    @php
        $isEdit = isset($entry) && $entry !== null;
        $formAction = $isEdit ? route('lembar-kontrol.update', $entry) : route('add-data-kontrol.store');
        $backUrl = route('lembar-kontrol', ['month' => $currentPeriod['month'], 'year' => $currentPeriod['year']]);
        $currentType = old('transaction_type', $isEdit ? $entry->transaction_type : 'operasional_langsung');
        $currentStatus = $isEdit ? $entry->status : 'Otomatis ditentukan sistem';
        $settledAmount = $isEdit && $entry->transaction_type === 'operasional_talangan' ? $entry->settledAmount() : 0;
        $remainingDebt = $isEdit && $entry->transaction_type === 'operasional_talangan' ? $entry->remainingDebt() : 0;
    @endphp

    <div class="space-y-6">
        <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-sky-600">{{ $isEdit ? 'Form Revisi Kontrol' : 'Form Input Kontrol' }}</p>
                        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ $isEdit ? 'Edit transaksi tersimpan' : 'Tambah transaksi baru' }}</h2>
                        <p class="mt-2 text-sm text-slate-500">
                            Form ini sekarang difokuskan untuk transaksi operasional harian, baik yang langsung dibayar maupun yang ditalangi, pada {{ $periodLabel }}.
                        </p>
                    </div>
                    <a href="{{ $backUrl }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700">
                        Kembali ke Tabel
                    </a>
                </div>

                <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data" class="mt-8 space-y-8">
                    @csrf
                    @if ($isEdit)
                        @method('PUT')
                    @endif

                    @if ($errors->any())
                        <div class="rounded-3xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700">
                            <p class="font-semibold">Masih ada data yang perlu diperbaiki.</p>
                            <ul class="mt-2 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <section class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-100 text-sm font-semibold text-sky-700">01</div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Jenis dan Waktu Transaksi</h3>
                                <p class="text-sm text-slate-500">Pilih jenis transaksi dulu supaya field berikutnya menyesuaikan otomatis.</p>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                            <div class="xl:col-span-3">
                                <label for="transaction_type" class="block text-sm font-medium text-slate-700">Jenis Transaksi</label>
                                <select id="transaction_type" name="transaction_type" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                    @foreach ($transactionTypes as $value => $label)
                                        <option value="{{ $value }}" @selected($currentType === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="entry_date" class="block text-sm font-medium text-slate-700">Hari, Tanggal</label>
                                <input id="entry_date" name="entry_date" type="date" value="{{ old('entry_date', $isEdit ? optional($entry->entry_date)->format('Y-m-d') : $defaultEntryDate) }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                <p class="mt-2 text-xs text-slate-500">Default tanggal mengikuti periode aktif {{ $periodLabel }}.</p>
                            </div>
                            <div>
                                <label for="handover_time" class="block text-sm font-medium text-slate-700">Waktu Penyerahan</label>
                                <select id="handover_time" name="handover_time" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                    <option value="">Pilih waktu penyerahan</option>
                                    @foreach ($handoverMoments as $moment)
                                        <option value="{{ $moment }}" @selected(old('handover_time', $isEdit ? $entry->handover_time : '') === $moment)>{{ $moment }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Status Sistem</label>
                                <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                    <span class="font-semibold text-slate-900">{{ $currentStatus }}</span>
                                    <p class="mt-1 text-xs text-slate-500">Status akan dihitung otomatis dari jenis transaksi dan settlement hutang.</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-sm font-semibold text-emerald-700">02</div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Nominal dan Settlement</h3>
                                <p class="text-sm text-slate-500">Nominal yang muncul menyesuaikan jenis transaksi yang dipilih.</p>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div data-panel="amount_out">
                                <label for="amount_out" class="block text-sm font-medium text-slate-700">Nominal Operasional / Kewajiban</label>
                                <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm transition focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100">
                                    <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                    <input id="amount_out" name="amount_out" type="number" min="0" value="{{ old('amount_out', $isEdit ? $entry->amount_out : '') }}" placeholder="0" class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                </div>
                                <p class="mt-2 text-xs text-slate-500">Dipakai untuk operasional langsung atau operasional yang ditalangi.</p>
                            </div>
                            <div data-panel="financier_name">
                                <label for="financier_name" class="block text-sm font-medium text-slate-700">Nama Pihak yang Menalangi</label>
                                <input id="financier_name" name="financier_name" type="text" value="{{ old('financier_name', $isEdit ? $entry->financier_name : '') }}" placeholder="Contoh: Bendahara / nama karyawan" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                <p class="mt-2 text-xs text-slate-500">Nama ini dipakai untuk menandai hutang talangan yang nanti dibayarkan kembali.</p>
                            </div>
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-4">
                                <p class="text-sm font-medium text-slate-700">Pelunasan Hutang</p>
                                <p class="mt-2 text-sm text-slate-500">
                                    Pelunasan hutang sekarang dilakukan dari halaman Dana Saving. Total hutang aktif saat ini:
                                    <span class="font-semibold text-slate-900">Rp {{ number_format($outstandingDebtTotal, 0, ',', '.') }}</span>
                                </p>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-violet-100 text-sm font-semibold text-violet-700">03</div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Pihak Terkait</h3>
                                <p class="text-sm text-slate-500">Isi pihak ketiga, petugas, pejabat, dan lokasi transaksi.</p>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="third_party" class="block text-sm font-medium text-slate-700">Pihak Ke-3</label>
                                <input id="third_party" name="third_party" type="text" value="{{ old('third_party', $isEdit ? $entry->third_party : '') }}" placeholder="Nama vendor atau pihak ketiga" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            </div>
                            <div>
                                <label for="receiving_officer" class="block text-sm font-medium text-slate-700">Petugas Penerima</label>
                                <input id="receiving_officer" name="receiving_officer" type="text" value="{{ old('receiving_officer', $isEdit ? $entry->receiving_officer : '') }}" placeholder="Nama petugas penerima" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            </div>
                            <div>
                                <label for="appointed_official" class="block text-sm font-medium text-slate-700">Pejabat yang Ditunjuk</label>
                                <input id="appointed_official" name="appointed_official" type="text" value="{{ old('appointed_official', $isEdit ? $entry->appointed_official : '') }}" placeholder="Nama pejabat penerima" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            </div>
                            <div>
                                <label for="location" class="block text-sm font-medium text-slate-700">Lokasi Penyerahan</label>
                                <input id="location" name="location" type="text" value="{{ old('location', $isEdit ? $entry->location : '') }}" placeholder="Lokasi transaksi / penyerahan" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-100 text-sm font-semibold text-amber-700">04</div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Klasifikasi Transaksi</h3>
                                <p class="text-sm text-slate-500">Sumber dana dikunci per jenis transaksi supaya hitungan dashboard tetap rapi.</p>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label for="purpose" class="block text-sm font-medium text-slate-700">Tujuan / Keperluan</label>
                                <textarea id="purpose" name="purpose" rows="4" placeholder="Contoh: pembayaran vendor konsumsi, operasional lapangan, atau kebutuhan yang ditalangi lebih dulu" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">{{ old('purpose', $isEdit ? $entry->purpose : '') }}</textarea>
                            </div>
                            <div>
                                <label for="fund_source" class="block text-sm font-medium text-slate-700">Sumber Dana</label>
                                <select id="fund_source" name="fund_source" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                    <option value="">Pilih sumber dana</option>
                                    <optgroup label="Operasional Langsung">
                                        @foreach ($directSources as $source)
                                            <option value="{{ $source }}" data-kind="operasional_langsung" @selected(old('fund_source', $isEdit ? $entry->fund_source : '') === $source)>{{ $source }}</option>
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="Talangan">
                                        @foreach ($talanganSources as $source)
                                            <option value="{{ $source }}" data-kind="operasional_talangan" @selected(old('fund_source', $isEdit ? $entry->fund_source : '') === $source)>{{ $source }}</option>
                                        @endforeach
                                    </optgroup>
                                </select>
                                <p id="fund_source_hint" class="mt-2 text-xs text-slate-500">
                                    Pilihan sumber dana menyesuaikan jenis transaksi operasional yang dipilih.
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Ringkasan Saat Ini</label>
                                <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                                    @if ($isEdit && $entry->transaction_type === 'operasional_talangan')
                                        <p>Total ditalangi: <span class="font-semibold text-slate-900">Rp {{ number_format($entry->obligation_amount, 0, ',', '.') }}</span></p>
                                        <p class="mt-2">Sudah dibayar: <span class="font-semibold text-sky-700">Rp {{ number_format($settledAmount, 0, ',', '.') }}</span></p>
                                        <p class="mt-2">Sisa hutang: <span class="font-semibold {{ $remainingDebt > 0 ? 'text-rose-700' : 'text-emerald-700' }}">Rp {{ number_format($remainingDebt, 0, ',', '.') }}</span></p>
                                    @else
                                        <p>Sistem akan membedakan otomatis mana operasional langsung dan mana hutang talangan.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-rose-100 text-sm font-semibold text-rose-700">05</div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Lampiran Bukti</h3>
                                <p class="text-sm text-slate-500">Upload PDF, JPG, atau PNG untuk melengkapi data transaksi.</p>
                            </div>
                        </div>

                        @if ($isEdit && $entry->proof_original_name)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                Bukti saat ini: <span class="font-medium text-slate-900">{{ $entry->proof_original_name }}</span>
                            </div>
                        @endif

                        <label for="proof_file" class="flex cursor-pointer flex-col items-center justify-center rounded-[28px] border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center transition hover:border-sky-300 hover:bg-sky-50/40">
                            <span class="rounded-full bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm">{{ $isEdit ? 'Ganti File Bukti' : 'Pilih File Bukti' }}</span>
                            <span class="mt-4 text-sm text-slate-500">PDF, JPG, atau PNG. Maksimal 5 MB per file.</span>
                            <input id="proof_file" name="proof_file" type="file" class="sr-only" />
                        </label>
                    </section>

                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
                        <a href="{{ $backUrl }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                            Batal
                        </a>
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700">
                            {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Data Kontrol' }}
                        </button>
                    </div>
                </form>
            </article>

            <div class="space-y-6">
                <article class="rounded-[28px] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-sky-950 p-6 text-white shadow-sm">
                    <p class="text-sm font-medium text-sky-200">{{ $isEdit ? 'Panduan Revisi' : 'Panduan Input Baru' }}</p>
                    <h3 class="mt-2 text-2xl font-semibold tracking-tight">Alur transaksi sekarang lebih aman</h3>
                    <div class="mt-5 space-y-4">
                        <div class="rounded-2xl border border-white/10 bg-white/8 p-4">
                            <p class="font-medium">1. Operasional langsung</p>
                            <p class="mt-1 text-sm text-slate-300">Dipakai jika kebutuhan langsung dibayar dari sumber dana yang tersedia, termasuk dana saving yang sudah siap dipakai.</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/8 p-4">
                            <p class="font-medium">2. Operasional ditalangi</p>
                            <p class="mt-1 text-sm text-slate-300">Dipakai jika bendahara atau karyawan memakai uang pribadi dulu. Sistem akan mencatat ini sebagai hutang talangan.</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/8 p-4">
                            <p class="font-medium">3. Pelunasan lewat dana saving</p>
                            <p class="mt-1 text-sm text-slate-300">Pelunasan hutang dilakukan dari halaman Dana Saving supaya pencairan dan penggunaan saving tidak dobel input.</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-emerald-600">Catatan Validasi</p>
                    <div class="mt-4 space-y-3 text-sm text-slate-600">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            Status `LUNAS`, `HUTANG`, dan `BAYAR SEBAGIAN` sekarang dihitung otomatis oleh sistem agar dashboard tidak rancu.
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            Dana saving yang dipakai langsung untuk operasional tetap boleh dipilih sebagai sumber dana pada transaksi operasional langsung.
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            Hutang talangan dilunasi dari halaman Dana Saving dan hanya untuk bulan serta tahun yang sama.
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const transactionType = document.getElementById('transaction_type');
            const fundSource = document.getElementById('fund_source');
            const fundSourceHint = document.getElementById('fund_source_hint');
            const panels = {
                amountOut: document.querySelector('[data-panel="amount_out"]'),
                financier: document.querySelector('[data-panel="financier_name"]'),
            };

            const hintMap = {
                operasional_langsung: 'Pilih sumber dana operasional atau sumber dana saving yang memang dipakai langsung untuk transaksi ini.',
                operasional_talangan: 'Pilih apakah transaksi ini ditalangi bendahara atau karyawan.',
            };

            function syncSourceOptions() {
                const currentType = transactionType.value;
                let selectedStillValid = false;

                Array.from(fundSource.options).forEach((option) => {
                    if (!option.value) {
                        return;
                    }

                    const allowedKinds = (option.dataset.kind || '').split(',');
                    const isAllowed = allowedKinds.includes(currentType);
                    option.hidden = !isAllowed;
                    option.disabled = !isAllowed;

                    if (isAllowed && option.selected) {
                        selectedStillValid = true;
                    }
                });

                if (!selectedStillValid) {
                    fundSource.value = '';
                }

                fundSourceHint.textContent = hintMap[currentType] ?? '';
            }

            function syncPanels() {
                const currentType = transactionType.value;
                const isOperational = currentType === 'operasional_langsung' || currentType === 'operasional_talangan';

                panels.amountOut.classList.toggle('hidden', !isOperational);
                panels.financier.classList.toggle('hidden', currentType !== 'operasional_talangan');

                syncSourceOptions();
            }

            transactionType.addEventListener('change', syncPanels);
            syncPanels();
        });
    </script>
</x-layout>
