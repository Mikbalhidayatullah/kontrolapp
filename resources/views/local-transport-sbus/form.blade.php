<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    @php
        $isEdit = isset($entry) && $entry !== null;
        $selectedType = $isEdit
            ? ($forcedType ?? 'transport_local')
            : old('sbu_type', 'transport_local');
        $selectedTypeLabel = $sbuTypes[$selectedType] ?? 'SBU';
    @endphp

    <div class="space-y-6">
        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-amber-600">{{ $isEdit ? 'Form Edit SBU' : 'Form Tambah SBU' }}</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ $isEdit ? 'Edit acuan '.$selectedTypeLabel : 'Tambah acuan SBU sesuai kelompok master' }}</h2>
                    @unless($isEdit)
                        <p class="mt-2 text-sm text-slate-500">Pilih dulu jenis master SBU yang ingin ditambahkan, lalu form akan menyesuaikan otomatis.</p>
                    @endunless
                </div>
                <a href="{{ route('local-transport-sbus.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700">
                    Kembali ke Daftar SBU
                </a>
            </div>

            <form action="{{ $isEdit ? route('local-transport-sbus.entries.update', ['type' => $selectedType, 'id' => $entry->id]) : route('local-transport-sbus.store') }}" method="POST" class="mt-8 space-y-8" data-sbu-form>
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                @unless($isEdit)
                    <section class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                        <label for="sbu_type" class="block text-sm font-medium text-slate-700">Pilih data SBU yang ingin diinput</label>
                        <select id="sbu_type" name="sbu_type" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" data-sbu-type-select>
                            @foreach ($sbuTypes as $value => $label)
                                <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </section>
                @else
                    <input type="hidden" name="sbu_type" value="{{ $selectedType }}" />
                @endunless

                <section data-sbu-form-panel="transport_local" class="space-y-6 {{ $selectedType === 'transport_local' ? '' : 'hidden' }}">
                    <div class="rounded-3xl border border-slate-200 bg-white p-5">
                        <p class="text-sm font-medium text-sky-600">Transport Lokal</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-900">Acuan rute transport lokal dalam daerah</h3>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                        <div class="xl:col-span-2">
                            <label for="area_name" class="block text-sm font-medium text-slate-700">Kelompok Wilayah</label>
                            <input id="area_name" name="area_name" type="text" value="{{ old('area_name', $isEdit ? $entry->area_name : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                        <div>
                            <label for="row_code" class="block text-sm font-medium text-slate-700">Kode Baris</label>
                            <input id="row_code" name="row_code" type="text" value="{{ old('row_code', $isEdit ? $entry->row_code : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                        <div>
                            <label for="sort_order" class="block text-sm font-medium text-slate-700">Urutan Tampil</label>
                            <input id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $isEdit ? $entry->sort_order : 0) }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                        <div class="xl:col-span-2">
                            <label for="origin_regency" class="block text-sm font-medium text-slate-700">Kabupaten/Kota Asal</label>
                            <input id="origin_regency" name="origin_regency" type="text" value="{{ old('origin_regency', $isEdit ? $entry->origin_regency : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                        <div class="xl:col-span-2">
                            <label for="origin_label" class="block text-sm font-medium text-slate-700">Titik Asal</label>
                            <input id="origin_label" name="origin_label" type="text" value="{{ old('origin_label', $isEdit ? $entry->origin_label : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                        <div class="xl:col-span-2">
                            <label for="destination_regency" class="block text-sm font-medium text-slate-700">Kabupaten/Kota Tujuan</label>
                            <input id="destination_regency" name="destination_regency" type="text" value="{{ old('destination_regency', $isEdit ? $entry->destination_regency : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                        <div class="xl:col-span-2">
                            <label for="destination_label" class="block text-sm font-medium text-slate-700">Tujuan / Kecamatan</label>
                            <input id="destination_label" name="destination_label" type="text" value="{{ old('destination_label', $isEdit ? $entry->destination_label : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                        <div>
                            <label for="unit_label" class="block text-sm font-medium text-slate-700">Satuan</label>
                            <input id="unit_label" name="unit_label" type="text" value="{{ old('unit_label', $isEdit ? $entry->unit_label : 'Orang/kali') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                        <div>
                            <label for="amount" class="block text-sm font-medium text-slate-700">Nominal</label>
                            <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm">
                                <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                <input id="amount" name="amount" type="text" value="{{ old('amount', $isEdit ? $entry->amount : '') }}" data-nominal-input class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                            </div>
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                                <input type="hidden" name="is_active" value="0" />
                                <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $isEdit ? $entry->is_active : true)) class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                                Data aktif dipakai sebagai acuan
                            </label>
                        </div>
                        <div class="xl:col-span-4">
                            <label for="notes" class="block text-sm font-medium text-slate-700">Catatan</label>
                            <textarea id="notes" name="notes" rows="4" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">{{ old('notes', $isEdit ? $entry->notes : '') }}</textarea>
                        </div>
                    </div>
                </section>

                <section data-sbu-form-panel="transport_taxi" class="space-y-6 {{ $selectedType === 'transport_taxi' ? '' : 'hidden' }}">
                        <div class="rounded-3xl border border-slate-200 bg-white p-5">
                            <p class="text-sm font-medium text-sky-600">Taksi Bandara</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-900">Acuan biaya maksimal transport taksi bandara</h3>
                        </div>
                        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label for="taxi_row_code" class="block text-sm font-medium text-slate-700">Kode Baris</label>
                                <input id="taxi_row_code" name="row_code" type="text" value="{{ old('row_code', $selectedType === 'transport_taxi' && $isEdit ? $entry->row_code : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            </div>
                            <div>
                                <label for="taxi_sort_order" class="block text-sm font-medium text-slate-700">Urutan Tampil</label>
                                <input id="taxi_sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $selectedType === 'transport_taxi' && $isEdit ? $entry->sort_order : 0) }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            </div>
                            <div class="xl:col-span-2">
                                <label for="taxi_origin_label" class="block text-sm font-medium text-slate-700">Titik Asal</label>
                                <input id="taxi_origin_label" name="origin_label" type="text" value="{{ old('origin_label', $selectedType === 'transport_taxi' && $isEdit ? $entry->origin_label : 'Bandara') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            </div>
                            <div class="xl:col-span-2">
                                <label for="taxi_destination_label" class="block text-sm font-medium text-slate-700">Tujuan</label>
                                <input id="taxi_destination_label" name="destination_label" type="text" value="{{ old('destination_label', $selectedType === 'transport_taxi' && $isEdit ? $entry->destination_label : 'Perjalanan Dinas Pergi Pulang (PP) Bandara') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            </div>
                            <div>
                                <label for="taxi_unit_label" class="block text-sm font-medium text-slate-700">Satuan</label>
                                <input id="taxi_unit_label" name="unit_label" type="text" value="{{ old('unit_label', $selectedType === 'transport_taxi' && $isEdit ? $entry->unit_label : 'Orang/kali') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            </div>
                            <div>
                                <label for="taxi_amount" class="block text-sm font-medium text-slate-700">Nominal</label>
                                <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm"><span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span><input id="taxi_amount" name="amount" type="text" value="{{ old('amount', $selectedType === 'transport_taxi' && $isEdit ? $entry->amount : '') }}" data-nominal-input class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" /></div>
                            </div>
                            <div class="flex items-end">
                                <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                                    <input type="hidden" name="is_active" value="0" />
                                    <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $selectedType === 'transport_taxi' && $isEdit ? $entry->is_active : true)) class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                                    Data aktif dipakai sebagai acuan
                                </label>
                            </div>
                            <div class="xl:col-span-4">
                                <label for="taxi_notes" class="block text-sm font-medium text-slate-700">Catatan</label>
                                <textarea id="taxi_notes" name="notes" rows="4" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">{{ old('notes', $selectedType === 'transport_taxi' && $isEdit ? $entry->notes : 'Provinsi Maluku Utara - Orang/Kali') }}</textarea>
                            </div>
                        </div>
                    </section>

                    <section data-sbu-form-panel="flight_ticket" class="space-y-6 {{ $selectedType === 'flight_ticket' ? '' : 'hidden' }}">
                        <div class="rounded-3xl border border-slate-200 bg-white p-5">
                            <p class="text-sm font-medium text-sky-600">Tiket Pesawat</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-900">Acuan tiket pesawat perjalanan dinas</h3>
                        </div>
                        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                            <div class="xl:col-span-2">
                                <label for="origin_city" class="block text-sm font-medium text-slate-700">Kota Asal</label>
                                <input id="origin_city" name="origin_city" type="text" value="{{ old('origin_city', $selectedType === 'flight_ticket' && $isEdit ? $entry->origin_city : 'TERNATE') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm uppercase text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            </div>
                            <div class="xl:col-span-2">
                                <label for="destination_city" class="block text-sm font-medium text-slate-700">Kota Tujuan</label>
                                <input id="destination_city" name="destination_city" type="text" value="{{ old('destination_city', $selectedType === 'flight_ticket' && $isEdit ? $entry->destination_city : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm uppercase text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            </div>
                            <div>
                                <label for="business_amount" class="block text-sm font-medium text-slate-700">Nominal Bisnis</label>
                                <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm"><span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span><input id="business_amount" name="business_amount" type="text" value="{{ old('business_amount', $selectedType === 'flight_ticket' && $isEdit ? $entry->business_amount : '') }}" data-nominal-input class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" /></div>
                            </div>
                            <div>
                                <label for="economy_amount" class="block text-sm font-medium text-slate-700">Nominal Ekonomi</label>
                                <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm"><span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span><input id="economy_amount" name="economy_amount" type="text" value="{{ old('economy_amount', $selectedType === 'flight_ticket' && $isEdit ? $entry->economy_amount : '') }}" data-nominal-input class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" /></div>
                            </div>
                            <div>
                                <label for="flight_sort_order" class="block text-sm font-medium text-slate-700">Urutan Tampil</label>
                                <input id="flight_sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $selectedType === 'flight_ticket' && $isEdit ? $entry->sort_order : 0) }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            </div>
                            <div class="flex items-end">
                                <label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                                    <input type="hidden" name="is_active" value="0" />
                                    <input name="is_active" type="checkbox" value="1" @checked(old('is_active', $selectedType === 'flight_ticket' && $isEdit ? $entry->is_active : true)) class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                                    Data aktif dipakai sebagai acuan
                                </label>
                            </div>
                        </div>
                    </section>
                    <section data-sbu-form-panel="lodging_regional" class="space-y-6 {{ $selectedType === 'lodging_regional' ? '' : 'hidden' }}">
                        <div class="rounded-3xl border border-slate-200 bg-white p-5">
                            <p class="text-sm font-medium text-sky-600">Penginapan Dalam Daerah</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-900">Acuan penginapan wilayah Maluku Utara</h3>
                        </div>
                        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                            <div class="xl:col-span-2"><label for="region_name" class="block text-sm font-medium text-slate-700">Wilayah</label><input id="region_name" name="region_name" type="text" value="{{ old('region_name', $selectedType === 'lodging_regional' && $isEdit ? $entry->region_name : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm uppercase text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" /></div>
                            <div><label for="lodging_regional_unit_label" class="block text-sm font-medium text-slate-700">Satuan</label><input id="lodging_regional_unit_label" name="unit_label" type="text" value="{{ old('unit_label', $selectedType === 'lodging_regional' && $isEdit ? $entry->unit_label : 'OH') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" /></div>
                            <div><label for="lodging_regional_sort_order" class="block text-sm font-medium text-slate-700">Urutan Tampil</label><input id="lodging_regional_sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $selectedType === 'lodging_regional' && $isEdit ? $entry->sort_order : 0) }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" /></div>
                            <div><label for="head_region_amount" class="block text-sm font-medium text-slate-700">Kepala Daerah / Eselon I</label><div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm"><span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span><input id="head_region_amount" name="head_region_amount" type="text" value="{{ old('head_region_amount', $selectedType === 'lodging_regional' && $isEdit ? $entry->head_region_amount : '') }}" data-nominal-input class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" /></div></div>
                            <div><label for="member_eselon_2_amount" class="block text-sm font-medium text-slate-700">Anggota DPRD / Eselon II</label><div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm"><span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span><input id="member_eselon_2_amount" name="member_eselon_2_amount" type="text" value="{{ old('member_eselon_2_amount', $selectedType === 'lodging_regional' && $isEdit ? $entry->member_eselon_2_amount : '') }}" data-nominal-input class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" /></div></div>
                            <div><label for="eselon_3_gol_4_amount" class="block text-sm font-medium text-slate-700">Eselon III / Gol. IV</label><div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm"><span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span><input id="eselon_3_gol_4_amount" name="eselon_3_gol_4_amount" type="text" value="{{ old('eselon_3_gol_4_amount', $selectedType === 'lodging_regional' && $isEdit ? $entry->eselon_3_gol_4_amount : '') }}" data-nominal-input class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" /></div></div>
                            <div><label for="eselon_4_gol_3_2_1_amount" class="block text-sm font-medium text-slate-700">Eselon IV / Gol. III, II, I</label><div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm"><span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span><input id="eselon_4_gol_3_2_1_amount" name="eselon_4_gol_3_2_1_amount" type="text" value="{{ old('eselon_4_gol_3_2_1_amount', $selectedType === 'lodging_regional' && $isEdit ? $entry->eselon_4_gol_3_2_1_amount : '') }}" data-nominal-input class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" /></div></div>
                            <div class="flex items-end"><label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700"><input type="hidden" name="is_active" value="0" /><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $selectedType === 'lodging_regional' && $isEdit ? $entry->is_active : true)) class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />Data aktif dipakai sebagai acuan</label></div>
                        </div>
                    </section>

                    <section data-sbu-form-panel="lodging_national" class="space-y-6 {{ $selectedType === 'lodging_national' ? '' : 'hidden' }}">
                        <div class="rounded-3xl border border-slate-200 bg-white p-5">
                            <p class="text-sm font-medium text-sky-600">Penginapan Luar Daerah</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-900">Acuan penginapan nasional</h3>
                        </div>
                        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                            <div class="xl:col-span-2"><label for="province_name" class="block text-sm font-medium text-slate-700">Provinsi</label><input id="province_name" name="province_name" type="text" value="{{ old('province_name', $selectedType === 'lodging_national' && $isEdit ? $entry->province_name : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm uppercase text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" /></div>
                            <div><label for="lodging_national_unit_label" class="block text-sm font-medium text-slate-700">Satuan</label><input id="lodging_national_unit_label" name="unit_label" type="text" value="{{ old('unit_label', $selectedType === 'lodging_national' && $isEdit ? $entry->unit_label : 'OH') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" /></div>
                            <div><label for="lodging_national_sort_order" class="block text-sm font-medium text-slate-700">Urutan Tampil</label><input id="lodging_national_sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $selectedType === 'lodging_national' && $isEdit ? $entry->sort_order : 0) }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" /></div>
                            <div><label for="national_head_region_amount" class="block text-sm font-medium text-slate-700">Kepala Daerah / Eselon I</label><div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm"><span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span><input id="national_head_region_amount" name="head_region_amount" type="text" value="{{ old('head_region_amount', $selectedType === 'lodging_national' && $isEdit ? $entry->head_region_amount : '') }}" data-nominal-input class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" /></div></div>
                            <div><label for="national_member_eselon_2_amount" class="block text-sm font-medium text-slate-700">Anggota DPRD / Eselon II</label><div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm"><span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span><input id="national_member_eselon_2_amount" name="member_eselon_2_amount" type="text" value="{{ old('member_eselon_2_amount', $selectedType === 'lodging_national' && $isEdit ? $entry->member_eselon_2_amount : '') }}" data-nominal-input class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" /></div></div>
                            <div><label for="national_eselon_3_gol_4_amount" class="block text-sm font-medium text-slate-700">Eselon III / Gol. IV</label><div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm"><span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span><input id="national_eselon_3_gol_4_amount" name="eselon_3_gol_4_amount" type="text" value="{{ old('eselon_3_gol_4_amount', $selectedType === 'lodging_national' && $isEdit ? $entry->eselon_3_gol_4_amount : '') }}" data-nominal-input class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" /></div></div>
                            <div><label for="national_eselon_4_gol_3_2_1_amount" class="block text-sm font-medium text-slate-700">Eselon IV / Gol. III, II, I</label><div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm"><span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span><input id="national_eselon_4_gol_3_2_1_amount" name="eselon_4_gol_3_2_1_amount" type="text" value="{{ old('eselon_4_gol_3_2_1_amount', $selectedType === 'lodging_national' && $isEdit ? $entry->eselon_4_gol_3_2_1_amount : '') }}" data-nominal-input class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" /></div></div>
                            <div class="flex items-end"><label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700"><input type="hidden" name="is_active" value="0" /><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $selectedType === 'lodging_national' && $isEdit ? $entry->is_active : true)) class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />Data aktif dipakai sebagai acuan</label></div>
                        </div>
                    </section>

                    <section data-sbu-form-panel="representation" class="space-y-6 {{ $selectedType === 'representation' ? '' : 'hidden' }}">
                        <div class="rounded-3xl border border-slate-200 bg-white p-5">
                            <p class="text-sm font-medium text-sky-600">Uang Representasi</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-900">Acuan uang representasi perjalanan dinas</h3>
                        </div>
                        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                            <div class="xl:col-span-2"><label for="position_group" class="block text-sm font-medium text-slate-700">Kelompok Jabatan</label><input id="position_group" name="position_group" type="text" value="{{ old('position_group', $selectedType === 'representation' && $isEdit ? $entry->position_group : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm uppercase text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" /></div>
                            <div><label for="representation_unit_label" class="block text-sm font-medium text-slate-700">Satuan</label><input id="representation_unit_label" name="unit_label" type="text" value="{{ old('unit_label', $selectedType === 'representation' && $isEdit ? $entry->unit_label : 'OH') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" /></div>
                            <div><label for="representation_sort_order" class="block text-sm font-medium text-slate-700">Urutan Tampil</label><input id="representation_sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $selectedType === 'representation' && $isEdit ? $entry->sort_order : 0) }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" /></div>
                            <div><label for="outside_city_amount" class="block text-sm font-medium text-slate-700">Luar Kota</label><div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm"><span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span><input id="outside_city_amount" name="outside_city_amount" type="text" value="{{ old('outside_city_amount', $selectedType === 'representation' && $isEdit ? $entry->outside_city_amount : '') }}" data-nominal-input class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" /></div></div>
                            <div><label for="inside_city_over_8_hours_amount" class="block text-sm font-medium text-slate-700">Dalam Kota > 8 Jam</label><div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm"><span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span><input id="inside_city_over_8_hours_amount" name="inside_city_over_8_hours_amount" type="text" value="{{ old('inside_city_over_8_hours_amount', $selectedType === 'representation' && $isEdit ? $entry->inside_city_over_8_hours_amount : '') }}" data-nominal-input class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" /></div></div>
                            <div class="flex items-end"><label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700"><input type="hidden" name="is_active" value="0" /><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $selectedType === 'representation' && $isEdit ? $entry->is_active : true)) class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />Data aktif dipakai sebagai acuan</label></div>
                        </div>
                    </section>

                    <section data-sbu-form-panel="daily_allowance" class="space-y-6 {{ $selectedType === 'daily_allowance' ? '' : 'hidden' }}">
                        <div class="rounded-3xl border border-slate-200 bg-white p-5">
                            <p class="text-sm font-medium text-sky-600">Uang Harian</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-900">Acuan uang harian perjalanan dinas</h3>
                        </div>
                        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                            <div class="xl:col-span-2"><label for="daily_province_name" class="block text-sm font-medium text-slate-700">Provinsi</label><input id="daily_province_name" name="province_name" type="text" value="{{ old('province_name', $selectedType === 'daily_allowance' && $isEdit ? $entry->province_name : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm uppercase text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" /></div>
                            <div><label for="daily_unit_label" class="block text-sm font-medium text-slate-700">Satuan</label><input id="daily_unit_label" name="unit_label" type="text" value="{{ old('unit_label', $selectedType === 'daily_allowance' && $isEdit ? $entry->unit_label : 'OH') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" /></div>
                            <div><label for="daily_sort_order" class="block text-sm font-medium text-slate-700">Urutan Tampil</label><input id="daily_sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $selectedType === 'daily_allowance' && $isEdit ? $entry->sort_order : 0) }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" /></div>
                            <div><label for="daily_outside_city_amount" class="block text-sm font-medium text-slate-700">Luar Kota</label><div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm"><span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span><input id="daily_outside_city_amount" name="outside_city_amount" type="text" value="{{ old('outside_city_amount', $selectedType === 'daily_allowance' && $isEdit ? $entry->outside_city_amount : '') }}" data-nominal-input class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" /></div></div>
                            <div><label for="sofifi_inside_city_over_8_hours_amount" class="block text-sm font-medium text-slate-700">Dalam Kota Sofifi > 8 Jam</label><div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm"><span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span><input id="sofifi_inside_city_over_8_hours_amount" name="sofifi_inside_city_over_8_hours_amount" type="text" value="{{ old('sofifi_inside_city_over_8_hours_amount', $selectedType === 'daily_allowance' && $isEdit ? $entry->sofifi_inside_city_over_8_hours_amount : '') }}" data-nominal-input class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" /></div></div>
                            <div><label for="diklat_amount" class="block text-sm font-medium text-slate-700">Diklat</label><div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm"><span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span><input id="diklat_amount" name="diklat_amount" type="text" value="{{ old('diklat_amount', $selectedType === 'daily_allowance' && $isEdit ? $entry->diklat_amount : '') }}" data-nominal-input class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" /></div></div>
                            <div class="flex items-end"><label class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700"><input type="hidden" name="is_active" value="0" /><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $selectedType === 'daily_allowance' && $isEdit ? $entry->is_active : true)) class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />Data aktif dipakai sebagai acuan</label></div>
                        </div>
                    </section>
                <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
                    <a href="{{ route('local-transport-sbus.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">Batal</a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700">{{ $isEdit ? 'Perbarui SBU' : 'Simpan SBU' }}</button>
                </div>
            </form>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-sbu-form]');
            const typeSelect = document.querySelector('[data-sbu-type-select]');
            if (!form || !typeSelect) return;
            const panels = Array.from(form.querySelectorAll('[data-sbu-form-panel]'));
            const syncPanels = () => {
                const current = typeSelect.value;
                panels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.dataset.sbuFormPanel !== current);
                });
            };
            typeSelect.addEventListener('change', syncPanels);
            syncPanels();
        });
    </script>

    <x-nominal-input-script />
</x-layout>
