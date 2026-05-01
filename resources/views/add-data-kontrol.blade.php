<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    @php
        $isEdit = isset($entry) && $entry !== null;
        $formAction = $isEdit ? route('lembar-kontrol.update', $entry) : route('add-data-kontrol.store');
        $backUrl = route('lembar-kontrol', ['month' => $currentPeriod['month'], 'year' => $currentPeriod['year']]);
        $currentSource = old('fund_source', $isEdit ? $entry->fund_source : '');
        $currentStatus = old('status', $isEdit ? $entry->status : 'LUNAS');
    @endphp

    <div class="space-y-6">
        <section>
            <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-sky-600">{{ $isEdit ? 'Form Revisi Kontrol' : 'Form Input Kontrol' }}</p>
                        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ $isEdit ? 'Edit transaksi tersimpan' : 'Tambah transaksi baru' }}</h2>
                        <p class="mt-2 text-sm text-slate-500">
                            Form ini sekarang difokuskan untuk transaksi operasional harian yang dibayar langsung pada {{ $periodLabel }}.
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

                    <section class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-100 text-sm font-semibold text-sky-700">01</div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Waktu Transaksi</h3>
                                <p class="text-sm text-slate-500">Isi tanggal dan waktu penyerahan transaksi operasional.</p>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
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
                                    <span class="font-semibold text-slate-900">Mengikuti sumber dana</span>
                                    <p class="mt-1 text-xs text-slate-500">Pilihan status ada di bagian sumber dana agar lebih mudah dibaca.</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-sm font-semibold text-emerald-700">02</div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Nominal Operasional</h3>
                                <p class="text-sm text-slate-500">Isi nominal operasional untuk transaksi ini.</p>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div data-panel="amount_out">
                                <label for="amount_out" class="block text-sm font-medium text-slate-700">Nominal Operasional / Kewajiban</label>
                                <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm transition focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100">
                                    <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                    <input id="amount_out" name="amount_out" type="text" value="{{ old('amount_out', $isEdit ? $entry->amount_out : '') }}" placeholder="0" data-nominal-input class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                </div>
                                <p class="mt-2 text-xs text-slate-500">Nominal pengeluaran operasional yang dibayar langsung.</p>
                            </div>
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-4">
                                <p class="text-sm font-medium text-slate-700">Catatan Input</p>
                                <p class="mt-2 text-sm text-slate-500">Data ini akan langsung tercatat sebagai operasional dibayar langsung dan dibaca oleh dashboard sesuai periode aktif.</p>
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
                                <h3 class="text-lg font-semibold text-slate-900">Sumber Dana</h3>
                                <p class="text-sm text-slate-500">Pilih sumber dana yang dipakai untuk transaksi ini.</p>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label for="purpose" class="block text-sm font-medium text-slate-700">Tujuan / Keperluan</label>
                                <textarea id="purpose" name="purpose" rows="4" placeholder="Contoh: pembayaran vendor konsumsi, operasional lapangan, atau kebutuhan kegiatan harian" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">{{ old('purpose', $isEdit ? $entry->purpose : '') }}</textarea>
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
                                </select>
                                <p id="fund_source_hint" class="mt-2 text-xs text-slate-500">
                                    Pilihan sumber dana disesuaikan untuk transaksi dibayar langsung.
                                </p>
                            </div>
                            <div data-panel="status_wrap">
                                <label class="block text-sm font-medium text-slate-700">Status Transaksi</label>
                                <div id="status_auto_info" class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                    <span class="font-semibold text-slate-900">LUNAS</span>
                                    <p class="mt-1 text-xs text-slate-500">Untuk sumber dana saving atau sumber selain YULIA dan VIVI, status dibuat otomatis.</p>
                                </div>
                                <div id="status_manual_wrap" class="mt-2 hidden">
                                    <select id="status" name="status" class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                        <option value="LUNAS" @selected($currentStatus === 'LUNAS')>LUNAS</option>
                                        <option value="HUTANG" @selected($currentStatus === 'HUTANG')>HUTANG</option>
                                    </select>
                                    <p class="mt-2 text-xs text-slate-500">Pilihan ini hanya dipakai jika sumber dana yang dipilih adalah YULIA atau VIVI.</p>
                                </div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">Ringkasan Saat Ini</label>
                                <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                                    <p>Sistem akan menyimpan transaksi ini sebagai operasional dibayar langsung sesuai sumber dana yang kamu pilih.</p>
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
                            <div id="current-proof-wrapper" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                Bukti saat ini:
                                <a href="{{ route('lembar-kontrol.proof', $entry) }}" target="_blank" rel="noopener noreferrer" class="font-medium text-sky-700 transition hover:text-sky-800 hover:underline">
                                    {{ $entry->proof_original_name }}
                                </a>
                            </div>
                        @endif

                        <label for="proof_file" class="flex cursor-pointer flex-col items-center justify-center rounded-[28px] border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center transition hover:border-sky-300 hover:bg-sky-50/40">
                            <span class="rounded-full bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm">{{ $isEdit ? 'Ganti File Bukti' : 'Pilih File Bukti' }}</span>
                            <span class="mt-4 text-sm text-slate-500">PDF, JPG, atau PNG. Maksimal 5 MB per file.</span>
                            <span id="proof_file_name" class="mt-3 text-sm font-medium text-sky-700"></span>
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
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fundSource = document.getElementById('fund_source');
            const fundSourceHint = document.getElementById('fund_source_hint');
            const statusInput = document.getElementById('status');
            const statusAutoInfo = document.getElementById('status_auto_info');
            const statusManualWrap = document.getElementById('status_manual_wrap');
            const manualStatusSources = @json($manualStatusSources);
            const proofFileInput = document.getElementById('proof_file');
            const proofFileName = document.getElementById('proof_file_name');
            const currentProofWrapper = document.getElementById('current-proof-wrapper');

            function syncStatusMode() {
                const selectedSource = fundSource.value;
                const isManualStatus = manualStatusSources.includes(selectedSource);

                statusAutoInfo.classList.toggle('hidden', isManualStatus);
                statusManualWrap.classList.toggle('hidden', !isManualStatus);

                if (!isManualStatus && statusInput) {
                    statusInput.value = 'LUNAS';
                }
            }

            fundSource.addEventListener('change', syncStatusMode);
            syncStatusMode();

            if (proofFileInput && proofFileName) {
                proofFileInput.addEventListener('change', () => {
                    const selectedFile = proofFileInput.files?.[0];

                    proofFileName.textContent = selectedFile ? `File dipilih: ${selectedFile.name}` : '';

                    if (currentProofWrapper) {
                        currentProofWrapper.classList.toggle('hidden', !!selectedFile);
                    }
                });
            }
        });
    </script>
    <x-nominal-input-script />
</x-layout>
