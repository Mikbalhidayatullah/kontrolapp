<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    @php
        $isEdit = isset($entry) && $entry !== null;
        $formAction = $isEdit ? route('lrfk.update', $entry) : route('lrfk.store');
        $moneyValue = fn ($value) => is_numeric($value) ? number_format((int) $value, 0, ',', '.') : $value;
    @endphp

    <div class="space-y-6">
        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-sky-600">{{ $isEdit ? 'Form Edit LRFK' : 'Form Input LRFK' }}</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ $isEdit ? 'Edit data LRFK' : 'Tambah data LRFK' }}</h2>
                    <p class="mt-2 text-sm text-slate-500">Isi program/kegiatan, kontrak, dan realisasi sesuai format LRFK.</p>
                </div>
                <a href="{{ route('lrfk.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700">
                    Kembali ke LRFK
                </a>
            </div>

            <form action="{{ $formAction }}" method="POST" class="mt-8 space-y-8">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <section class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-100 text-sm font-semibold text-sky-700">01</div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Data Program / Kegiatan</h3>
                            <p class="text-sm text-slate-500">Kode mengikuti jenis baris: Program, Kegiatan, Sub Kegiatan, atau Rekening.</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="level" class="block text-sm font-medium text-slate-700">Jenis Baris</label>
                            <select id="level" name="level" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                @foreach ($levelOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('level', $entry->level ?? 'rekening') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('level')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="kode" class="block text-sm font-medium text-slate-700">Kode</label>
                            <input id="kode" name="kode" type="text" value="{{ old('kode', $entry->kode ?? '') }}" placeholder="Contoh: Program / Kegiatan / Sub Kegiatan" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            @error('kode')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="kode_rekening" class="block text-sm font-medium text-slate-700">Kode Rekening</label>
                            <input id="kode_rekening" name="kode_rekening" type="text" value="{{ old('kode_rekening', $entry->kode_rekening ?? '') }}" placeholder="Contoh: 1.01.01.1.01.0001" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            @error('kode_rekening')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="pagu_anggaran" class="block text-sm font-medium text-slate-700">Pagu Anggaran</label>
                            <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm transition focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100">
                                <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                <input id="pagu_anggaran" name="pagu_anggaran" type="text" value="{{ old('pagu_anggaran', $moneyValue($entry->pagu_anggaran ?? '')) }}" data-lrfk-money class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                            </div>
                            @error('pagu_anggaran')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <label for="program_kegiatan" class="block text-sm font-medium text-slate-700">Program / Kegiatan / Sub Kegiatan</label>
                            <textarea id="program_kegiatan" name="program_kegiatan" rows="3" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">{{ old('program_kegiatan', $entry->program_kegiatan ?? '') }}</textarea>
                            @error('program_kegiatan')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-100 text-sm font-semibold text-amber-700">02</div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Kontrak</h3>
                            <p class="text-sm text-slate-500">Nilai, nomor/tanggal, pelaksana, keluaran, volume, dan satuan.</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="contract_value" class="block text-sm font-medium text-slate-700">Nilai Kontrak</label>
                            <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm transition focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100">
                                <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                <input id="contract_value" name="contract_value" type="text" value="{{ old('contract_value', $moneyValue($entry->contract_value ?? '')) }}" data-lrfk-money class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                            </div>
                        </div>
                        <div>
                            <label for="contract_number_date" class="block text-sm font-medium text-slate-700">Nomor / Tanggal</label>
                            <input id="contract_number_date" name="contract_number_date" type="text" value="{{ old('contract_number_date', $entry->contract_number_date ?? '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                        <div>
                            <label for="implementer" class="block text-sm font-medium text-slate-700">Pelaksana</label>
                            <input id="implementer" name="implementer" type="text" value="{{ old('implementer', $entry->implementer ?? '') }}" placeholder="Contoh: PT PLN / Dinas Pendidikan" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                        <div>
                            <label for="location" class="block text-sm font-medium text-slate-700">Lokasi</label>
                            <input id="location" name="location" type="text" value="{{ old('location', $entry->location ?? '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                        <div class="md:col-span-2">
                            <label for="output" class="block text-sm font-medium text-slate-700">Keluaran</label>
                            <textarea id="output" name="output" rows="3" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">{{ old('output', $entry->output ?? '') }}</textarea>
                        </div>
                        <div>
                            <label for="volume" class="block text-sm font-medium text-slate-700">Volume</label>
                            <input id="volume" name="volume" type="text" value="{{ old('volume', $entry->volume ?? '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                        <div>
                            <label for="unit" class="block text-sm font-medium text-slate-700">Satuan</label>
                            <input id="unit" name="unit" type="text" value="{{ old('unit', $entry->unit ?? '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-sm font-semibold text-emerald-700">03</div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Realisasi</h3>
                            <p class="text-sm text-slate-500">Persentase keuangan dan fisik dihitung otomatis dari realisasi dibagi pagu.</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="financial_realization" class="block text-sm font-medium text-slate-700">Realisasi Keuangan</label>
                            <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm transition focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100">
                                <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                <input id="financial_realization" name="financial_realization" type="text" value="{{ old('financial_realization', $moneyValue($entry->financial_realization ?? '')) }}" data-lrfk-money class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                            </div>
                        </div>
                        <div>
                            <label for="notes" class="block text-sm font-medium text-slate-700">Keterangan</label>
                            <textarea id="notes" name="notes" rows="3" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">{{ old('notes', $entry->notes ?? '') }}</textarea>
                        </div>
                    </div>
                </section>

                <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-6">
                    <a href="{{ route('lrfk.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700">
                        {{ $isEdit ? 'Simpan Perubahan' : 'Simpan LRFK' }}
                    </button>
                </div>
            </form>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const formatNumber = (value) => {
                const digits = String(value || '').replace(/\D/g, '');
                return digits ? new Intl.NumberFormat('id-ID').format(Number(digits)) : '';
            };

            document.querySelectorAll('[data-lrfk-money]').forEach((input) => {
                input.value = formatNumber(input.value);
                input.addEventListener('input', () => {
                    input.value = formatNumber(input.value);
                });
            });
        });
    </script>
</x-layout>
