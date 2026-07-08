@php
    $fieldName = fn (string $field): string => $prefix === '' ? $field : "{$prefix}[{$field}]";
    $fieldId = fn (string $field): string => "{$idPrefix}_{$field}";
    $fieldValue = fn (string $field, mixed $default = ''): mixed => $item[$field] ?? $default;
    $fieldError = fn (string $field): string => str_replace(['][', '[', ']'], ['.', '.', ''], $fieldName($field));
    $hasPpn = filled($fieldValue('ppn_amount')) || filled($fieldValue('ppn_billing_id')) || filled($fieldValue('ppn_ntpn'));
    $hasPph21 = filled($fieldValue('pph21_amount')) || filled($fieldValue('pph21_billing_id')) || filled($fieldValue('pph21_ntpn'));
    $hasPph22 = filled($fieldValue('pph22_amount')) || filled($fieldValue('pph22_billing_id')) || filled($fieldValue('pph22_ntpn'));
    $hasPph23 = filled($fieldValue('pph23_amount')) || filled($fieldValue('pph23_billing_id')) || filled($fieldValue('pph23_ntpn'));
@endphp

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label for="{{ $fieldId('kode_kegiatan') }}" class="block text-sm font-medium text-slate-700">Kode Kegiatan</label>
        <input id="{{ $fieldId('kode_kegiatan') }}" name="{{ $fieldName('kode_kegiatan') }}" type="text" value="{{ $fieldValue('kode_kegiatan') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
    </div>
    <div>
        <label for="{{ $fieldId('sp2d_number') }}" class="block text-sm font-medium text-slate-700">Nomor SP2D</label>
        <input id="{{ $fieldId('sp2d_number') }}" name="{{ $fieldName('sp2d_number') }}" type="text" value="{{ $fieldValue('sp2d_number') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
    </div>
    <div class="md:col-span-2">
        <label for="{{ $fieldId('nama_belanja') }}" class="block text-sm font-medium text-slate-700">Nama Belanja</label>
        <textarea id="{{ $fieldId('nama_belanja') }}" name="{{ $fieldName('nama_belanja') }}" rows="3" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">{{ $fieldValue('nama_belanja') }}</textarea>
        @error($fieldError('nama_belanja'))
            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="{{ $fieldId('sp2d_date') }}" class="block text-sm font-medium text-slate-700">Tanggal SP2D</label>
        <input id="{{ $fieldId('sp2d_date') }}" name="{{ $fieldName('sp2d_date') }}" type="date" value="{{ $fieldValue('sp2d_date') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
    </div>
    @foreach ([
        ['field' => 'pagu_amount', 'label' => 'Pagu'],
        ['field' => 'requested_amount', 'label' => 'Jumlah yang Diminta'],
    ] as $moneyField)
        <div>
            <label for="{{ $fieldId($moneyField['field']) }}" class="block text-sm font-medium text-slate-700">{{ $moneyField['label'] }}</label>
            <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm transition focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100">
                <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                <input id="{{ $fieldId($moneyField['field']) }}" name="{{ $fieldName($moneyField['field']) }}" type="text" value="{{ $moneyValue($fieldValue($moneyField['field'])) }}" data-tax-money-input data-tu-money="{{ $moneyField['field'] }}" class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
            </div>
        </div>
    @endforeach
</div>

<div class="mt-6 rounded-3xl border border-slate-200 bg-white p-5">
    <h5 class="text-sm font-semibold text-slate-900">Realisasi I - IV</h5>
    <div class="mt-4 grid gap-4 md:grid-cols-4">
        @foreach ([1, 2, 3, 4] as $number)
            <div class="rounded-2xl border border-slate-200 p-4">
                <p class="text-sm font-semibold text-slate-700">Realisasi {{ $number }}</p>
                <label for="{{ $fieldId("realization_{$number}_amount") }}" class="mt-3 block text-xs font-medium text-slate-500">Nilai</label>
                <div class="mt-1 flex overflow-hidden rounded-xl border border-slate-300 bg-white">
                    <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-3 text-xs text-slate-500">Rp</span>
                    <input id="{{ $fieldId("realization_{$number}_amount") }}" name="{{ $fieldName("realization_{$number}_amount") }}" type="text" value="{{ $moneyValue($fieldValue("realization_{$number}_amount")) }}" data-tax-money-input data-tu-money="realization" class="block w-full px-3 py-2 text-sm text-slate-900 outline-none" />
                </div>
                <label for="{{ $fieldId("realization_{$number}_date") }}" class="mt-3 block text-xs font-medium text-slate-500">Tanggal</label>
                <input id="{{ $fieldId("realization_{$number}_date") }}" name="{{ $fieldName("realization_{$number}_date") }}" type="date" value="{{ $fieldValue("realization_{$number}_date") }}" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
            </div>
        @endforeach
    </div>
</div>

<div class="mt-6 grid gap-5 md:grid-cols-2">
    <div class="rounded-3xl border border-slate-200 bg-white p-5">
        <h5 class="text-sm font-semibold text-slate-900">Surat Tanda Setoran</h5>
        <div class="mt-4 space-y-4">
            <div>
                <label for="{{ $fieldId('deposit_letter_number') }}" class="block text-sm font-medium text-slate-700">Nomor Surat</label>
                <input id="{{ $fieldId('deposit_letter_number') }}" name="{{ $fieldName('deposit_letter_number') }}" type="text" value="{{ $fieldValue('deposit_letter_number') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
            </div>
            <div>
                <label for="{{ $fieldId('deposit_amount') }}" class="block text-sm font-medium text-slate-700">Nilai Setoran</label>
                <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm transition focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100">
                    <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                    <input id="{{ $fieldId('deposit_amount') }}" name="{{ $fieldName('deposit_amount') }}" type="text" value="{{ $moneyValue($fieldValue('deposit_amount')) }}" data-tax-money-input data-tu-money="deposit" class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                </div>
                <p class="mt-2 text-xs text-slate-500">Sisa dana setoran dihitung dari sisa dana TU realisasi dikurangi nilai setoran.</p>
            </div>
            <div>
                <label for="{{ $fieldId('deposit_date') }}" class="block text-sm font-medium text-slate-700">Tanggal Setoran</label>
                <input id="{{ $fieldId('deposit_date') }}" name="{{ $fieldName('deposit_date') }}" type="date" value="{{ $fieldValue('deposit_date') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
            </div>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-5">
        <h5 class="text-sm font-semibold text-slate-900">Pilihan Pajak</h5>
        <div class="mt-4 flex flex-wrap gap-3">
            @foreach ([['key' => 'ppn', 'label' => 'PPN', 'checked' => $hasPpn], ['key' => 'pph21', 'label' => 'PPh 21', 'checked' => $hasPph21], ['key' => 'pph22', 'label' => 'PPh 22', 'checked' => $hasPph22], ['key' => 'pph23', 'label' => 'PPh 23', 'checked' => $hasPph23]] as $taxOption)
                <label class="inline-flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-800">
                    <input type="checkbox" data-tu-tax-toggle="{{ $taxOption['key'] }}" @checked($taxOption['checked']) class="h-4 w-4 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500" />
                    {{ $taxOption['label'] }}
                </label>
            @endforeach
        </div>

        <div class="mt-5 space-y-4">
            @foreach ([['key' => 'ppn', 'label' => 'PPN'], ['key' => 'pph21', 'label' => 'PPh 21'], ['key' => 'pph22', 'label' => 'PPh 22'], ['key' => 'pph23', 'label' => 'PPh 23']] as $taxField)
                <div data-tu-tax-panel="{{ $taxField['key'] }}" class="{{ filled($fieldValue($taxField['key'].'_amount')) || filled($fieldValue($taxField['key'].'_billing_id')) || filled($fieldValue($taxField['key'].'_ntpn')) ? '' : 'hidden' }} rounded-2xl border border-slate-200 p-4">
                    <p class="text-sm font-semibold text-slate-800">{{ $taxField['label'] }}</p>
                    <div class="mt-3 grid gap-3 md:grid-cols-3">
                        <div>
                            <label for="{{ $fieldId($taxField['key'].'_amount') }}" class="block text-xs font-medium text-slate-500">Nilai</label>
                            <div class="mt-1 flex overflow-hidden rounded-xl border border-slate-300 bg-white">
                                <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-3 text-xs text-slate-500">Rp</span>
                                <input id="{{ $fieldId($taxField['key'].'_amount') }}" name="{{ $fieldName($taxField['key'].'_amount') }}" type="text" value="{{ $moneyValue($fieldValue($taxField['key'].'_amount')) }}" data-tax-money-input class="block w-full px-3 py-2 text-sm text-slate-900 outline-none" />
                            </div>
                        </div>
                        <div>
                            <label for="{{ $fieldId($taxField['key'].'_billing_id') }}" class="block text-xs font-medium text-slate-500">ID Billing</label>
                            <input id="{{ $fieldId($taxField['key'].'_billing_id') }}" name="{{ $fieldName($taxField['key'].'_billing_id') }}" type="text" value="{{ $fieldValue($taxField['key'].'_billing_id') }}" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                        <div>
                            <label for="{{ $fieldId($taxField['key'].'_ntpn') }}" class="block text-xs font-medium text-slate-500">NTPN</label>
                            <input id="{{ $fieldId($taxField['key'].'_ntpn') }}" name="{{ $fieldName($taxField['key'].'_ntpn') }}" type="text" value="{{ $fieldValue($taxField['key'].'_ntpn') }}" class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="mt-5">
    <label for="{{ $fieldId('notes') }}" class="block text-sm font-medium text-slate-700">Keterangan</label>
    <textarea id="{{ $fieldId('notes') }}" name="{{ $fieldName('notes') }}" rows="3" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">{{ $fieldValue('notes') }}</textarea>
</div>
