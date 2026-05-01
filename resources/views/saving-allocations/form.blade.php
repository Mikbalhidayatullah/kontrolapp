<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    @php
        $isEdit = isset($allocation) && $allocation !== null;
        $formAction = $isEdit ? route('dana-saving.update', $allocation) : route('dana-saving.store');
        $backUrl = route('dana-saving.index', ['month' => $periodMonth, 'year' => $periodYear]);
    @endphp

    <div class="space-y-6">
        <section>
            <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-sky-600">{{ $isEdit ? 'Edit Dana Saving' : 'Input Dana Saving' }}</p>
                        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ $isEdit ? 'Perbarui nominal saving' : 'Tambah sumber dana saving baru' }}</h2>
                        <p class="mt-2 text-sm text-slate-500">Data di form ini masuk ke Tabel 2.1 dashboard dan tetap dipakai sebagai rekap penambahan saving pada periode {{ $periodLabel }}.</p>
                    </div>
                    <a href="{{ $backUrl }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700">
                        Kembali ke Daftar
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
                                <h3 class="text-lg font-semibold text-slate-900">Periode Dashboard</h3>
                                <p class="text-sm text-slate-500">Form ini otomatis mengikuti periode aktif {{ $periodLabel }} saat dibuka dari daftar dana saving, tetapi tetap bisa disiapkan untuk bulan atau tahun lain.</p>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="period_month" class="block text-sm font-medium text-slate-700">Bulan</label>
                                <select id="period_month" name="period_month" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                    @foreach ([
                                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
                                        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                    ] as $monthValue => $monthLabel)
                                        <option value="{{ $monthValue }}" @selected((int) old('period_month', $isEdit ? $allocation->period_month : $periodMonth) === $monthValue)>{{ $monthLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="period_year" class="block text-sm font-medium text-slate-700">Tahun</label>
                                <select id="period_year" name="period_year" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                    @foreach (range(now()->year - 2, now()->year + 5) as $yearOption)
                                        <option value="{{ $yearOption }}" @selected((int) old('period_year', $isEdit ? $allocation->period_year : $periodYear) === $yearOption)>{{ $yearOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-sm font-semibold text-emerald-700">02</div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Rincian Dana Saving</h3>
                                <p class="text-sm text-slate-500">Masukkan sumber saving dan nominalnya. Urutan tampil di dashboard sekarang diatur otomatis oleh sistem.</p>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label for="source_name" class="block text-sm font-medium text-slate-700">Sumber Dana Saving</label>
                                <select id="source_name" name="source_name" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                    <option value="">Pilih sumber dana saving</option>
                                    @foreach ($sourceOptions as $sourceOption)
                                        <option value="{{ $sourceOption }}" @selected(old('source_name', $isEdit ? $allocation->source_name : '') === $sourceOption)>{{ $sourceOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="amount" class="block text-sm font-medium text-slate-700">Nominal Dana Saving</label>
                                <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm transition focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100">
                                    <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                    <input id="amount" name="amount" type="text" value="{{ old('amount', $isEdit ? $allocation->amount : '') }}" placeholder="0" data-nominal-input class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-violet-100 text-sm font-semibold text-violet-700">03</div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Status Dashboard</h3>
                                <p class="text-sm text-slate-500">Atur apakah baris saving ini ikut dihitung pada rekap dashboard atau hanya disimpan sebagai histori.</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="flex items-center justify-between rounded-3xl border border-slate-200 bg-slate-50 px-5 py-4">
                                <div>
                                    <p class="font-semibold text-slate-900">Aktifkan untuk dashboard</p>
                                    <p class="mt-1 text-sm text-slate-500">Baris aktif akan masuk ke Tabel 2.1 dan Tabel 2.2.</p>
                                </div>
                                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $isEdit ? $allocation->is_active : true)) class="h-5 w-5 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                            </label>
                        </div>
                    </section>

                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
                        <a href="{{ $backUrl }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                            Batal
                        </a>
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700">
                            {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Dana Saving' }}
                        </button>
                    </div>
                </form>
            </article>
        </section>
    </div>
    <x-nominal-input-script />
</x-layout>
