<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    @php
        $isEdit = isset($reduction) && $reduction !== null;
        $formAction = $isEdit ? route('dana-saving.reductions.update', $reduction) : route('dana-saving.reductions.store');
        $backUrl = route('dana-saving.index', ['month' => $periodMonth, 'year' => $periodYear]);
    @endphp

    <div class="space-y-6">
        <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-rose-600">{{ $isEdit ? 'Edit Pengurangan Saving' : 'Input Pengurangan Saving' }}</p>
                        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ $isEdit ? 'Perbarui nominal pengurangan' : 'Catat pembayaran balik dana saving' }}</h2>
                        <p class="mt-2 text-sm text-slate-500">Form ini khusus untuk mengurangi kewajiban bayar balik dana saving pribadi pada periode {{ $periodLabel }}. Nilainya tidak mengubah total saving di dashboard.</p>
                    </div>
                    <a href="{{ $backUrl }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-rose-300 hover:text-rose-700">
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
                                <h3 class="text-lg font-semibold text-slate-900">Periode Pengurangan</h3>
                                <p class="text-sm text-slate-500">Pengurangan akan dicatat ke bulan dan tahun yang sedang dipilih.</p>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="period_month" class="block text-sm font-medium text-slate-700">Bulan</label>
                                <select id="period_month" name="period_month" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-100">
                                    @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $monthValue => $monthLabel)
                                        <option value="{{ $monthValue }}" @selected((int) old('period_month', $isEdit ? $reduction->period_month : $periodMonth) === $monthValue)>{{ $monthLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="period_year" class="block text-sm font-medium text-slate-700">Tahun</label>
                                <select id="period_year" name="period_year" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-100">
                                    @foreach (range(now()->year - 2, now()->year + 5) as $yearOption)
                                        <option value="{{ $yearOption }}" @selected((int) old('period_year', $isEdit ? $reduction->period_year : $periodYear) === $yearOption)>{{ $yearOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-rose-100 text-sm font-semibold text-rose-700">02</div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Rincian Pengurangan</h3>
                                <p class="text-sm text-slate-500">Pilih sumber saving yang sedang dibayarkan kembali, lalu isi nominal pengurangannya.</p>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label for="source_name" class="block text-sm font-medium text-slate-700">Sumber Dana Saving</label>
                                <select id="source_name" name="source_name" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-100">
                                    <option value="">Pilih sumber dana saving</option>
                                    @foreach ($sourceOptions as $sourceOption)
                                        <option value="{{ $sourceOption }}" @selected(old('source_name', $isEdit ? $reduction->source_name : '') === $sourceOption)>{{ $sourceOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="amount" class="block text-sm font-medium text-slate-700">Nominal Pengurangan</label>
                                <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm transition focus-within:border-rose-400 focus-within:ring-4 focus-within:ring-rose-100">
                                    <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                    <input id="amount" name="amount" type="text" value="{{ old('amount', $isEdit ? $reduction->amount : '') }}" placeholder="0" data-nominal-input class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                </div>
                            </div>
                            <div>
                                <label for="reduction_date" class="block text-sm font-medium text-slate-700">Tanggal Pengurangan</label>
                                <input id="reduction_date" name="reduction_date" type="date" value="{{ old('reduction_date', $isEdit && $reduction->reduction_date ? $reduction->reduction_date->format('Y-m-d') : now()->format('Y-m-d')) }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-100" />
                            </div>
                            <div class="md:col-span-2">
                                <label for="note" class="block text-sm font-medium text-slate-700">Catatan</label>
                                <textarea id="note" name="note" rows="4" placeholder="Contoh: pembayaran balik sebagian saving pribadi bendahara." class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-rose-400 focus:ring-4 focus:ring-rose-100">{{ old('note', $isEdit ? $reduction->note : '') }}</textarea>
                            </div>
                        </div>
                    </section>

                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
                        <a href="{{ $backUrl }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                            Batal
                        </a>
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-rose-700">
                            {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Pengurangan' }}
                        </button>
                    </div>
                </form>
            </article>

            <div class="space-y-6">
                <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-violet-600">Sisa per Sumber</p>
                    <div class="mt-4 space-y-3 text-sm text-slate-600">
                        @foreach ($sourceBalances as $balance)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="font-semibold text-slate-900">{{ $balance['source'] }}</p>
                                <p class="mt-1">Total saving: Rp {{ number_format($balance['total'], 0, ',', '.') }}</p>
                                <p class="mt-1 text-rose-600">Sudah dikurangi: Rp {{ number_format($balance['reduced'], 0, ',', '.') }}</p>
                                @if (($balance['transferred'] ?? 0) > 0 && ! empty($balance['transferred_label']))
                                    <p class="mt-1 text-amber-700">{{ $balance['transferred_label'] }}: Rp {{ number_format($balance['transferred'], 0, ',', '.') }}</p>
                                @endif
                                <p class="mt-1 text-violet-700">Sisa bayar balik: Rp {{ number_format($balance['remaining'], 0, ',', '.') }}</p>
                            </div>
                        @endforeach
                    </div>
                </article>
            </div>
        </section>
    </div>
    <x-nominal-input-script />
</x-layout>
