<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    @php
        $isEdit = isset($entry) && $entry !== null;
    @endphp

    <div class="space-y-6">
        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-amber-600">Form Input Perjadin</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ $isEdit ? 'Edit perjalanan dinas berdasarkan kategori' : 'Tambah perjalanan dinas berdasarkan kategori' }}</h2>
                </div>
                <a href="{{ route('perjadin', ['month' => $currentPeriod['month'], 'year' => $currentPeriod['year'], 'category' => $activeCategory, 'keyword' => $activeKeyword]) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700">
                    Kembali ke Perjadin
                </a>
            </div>

            <form action="{{ $isEdit ? route('perjadin.update', $entry) : route('add-perjadin.store') }}" method="POST" enctype="multipart/form-data" class="mt-8 space-y-8">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <section class="space-y-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-900 text-sm font-semibold text-white">00</div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Kategori Perjadin</h3>
                            <p class="text-sm text-slate-500">Pilih kategori perjalanan dinas yang sesuai.</p>
                        </div>
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-medium text-slate-700">Kategori</label>
                        <select id="category" name="category" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                            <option value="">Pilih kategori perjadin</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}" @selected(old('category', $isEdit ? $entry->category : $activeCategory) === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                </section>

                <section class="space-y-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-100 text-sm font-semibold text-amber-700">01</div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Informasi Umum</h3>
                            <p class="text-sm text-slate-500">Isi identitas pelaksana dan unit kerja.</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                        <div class="xl:col-span-2">
                            <label for="skpd_name" class="block text-sm font-medium text-slate-700">Nama SKPD</label>
                            <input id="skpd_name" name="skpd_name" type="text" value="{{ old('skpd_name', $isEdit ? $entry->skpd_name : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                        <div class="xl:col-span-2">
                            <label for="executor_name" class="block text-sm font-medium text-slate-700">Nama Pelaksana</label>
                            <input id="executor_name" name="executor_name" type="text" value="{{ old('executor_name', $isEdit ? $entry->executor_name : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                        <div class="xl:col-span-2">
                            <label for="position_name" class="block text-sm font-medium text-slate-700">Jabatan</label>
                            <input id="position_name" name="position_name" type="text" value="{{ old('position_name', $isEdit ? $entry->position_name : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                        <div>
                            <label for="grade" class="block text-sm font-medium text-slate-700">Golongan</label>
                            <select id="grade" name="grade" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                <option value="">Pilih golongan</option>
                                @foreach ($gradeOptions as $grade)
                                    <option value="{{ $grade }}" @selected(old('grade', $isEdit ? $entry->grade : '') === $grade)>{{ $grade }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                <section class="space-y-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-100 text-sm font-semibold text-sky-700">02</div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Jangka Waktu Surat Perintah Tugas</h3>
                            <p class="text-sm text-slate-500">Lengkapi tanggal dan tujuan perjalanan dinas.</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-6">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-slate-700">Dari</label>
                            <input id="start_date" name="start_date" type="date" value="{{ old('start_date', $isEdit && $entry->start_date ? $entry->start_date->format('Y-m-d') : $defaultStartDate) }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-slate-700">Sampai</label>
                            <input id="end_date" name="end_date" type="date" value="{{ old('end_date', $isEdit && $entry->end_date ? $entry->end_date->format('Y-m-d') : $defaultStartDate) }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                        <div class="xl:col-span-2">
                            <label for="assignment_number" class="block text-sm font-medium text-slate-700">No Surat Tugas</label>
                            <input id="assignment_number" name="assignment_number" type="text" value="{{ old('assignment_number', $isEdit ? $entry->assignment_number : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                        <div class="xl:col-span-1">
                            <label for="assignment_date" class="block text-sm font-medium text-slate-700">Tanggal Surat Tugas</label>
                            <input id="assignment_date" name="assignment_date" type="date" value="{{ old('assignment_date', $isEdit && $entry->assignment_date ? $entry->assignment_date->format('Y-m-d') : $defaultStartDate) }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                        <div class="xl:col-span-1">
                            <label for="signature_location" class="block text-sm font-medium text-slate-700">Lokasi TTD</label>
                            <input id="signature_location" name="signature_location" type="text" value="{{ old('signature_location', $isEdit ? $entry->signature_location : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                        <div class="xl:col-span-6">
                            <label for="destination_city" class="block text-sm font-medium text-slate-700">Kota / Kab Tujuan</label>
                            <input id="destination_city" name="destination_city" type="text" value="{{ old('destination_city', $isEdit ? $entry->destination_city : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        </div>
                    </div>
                </section>

                <section class="space-y-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-sm font-semibold text-emerald-700">03</div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Bukti Sesuai SPPD</h3>
                            <p class="text-sm text-slate-500">Aktifkan hanya komponen biaya yang digunakan.</p>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <article class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5" data-group>
                            <input type="hidden" name="daily_allowance_enabled" value="0" />
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h4 class="text-base font-semibold text-slate-900">Uang Harian</h4>
                                </div>
                                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                                    <input type="checkbox" name="daily_allowance_enabled" value="1" @checked(old('daily_allowance_enabled', $isEdit ? $entry->daily_allowance_enabled : false)) data-group-toggle class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                                    Aktif
                                </label>
                            </div>
                            <div class="mt-5 grid gap-5 md:grid-cols-3" data-group-body>
                                <div>
                                    <label for="daily_allowance_days" class="block text-sm font-medium text-slate-700">Jumlah Hari</label>
                                    <input id="daily_allowance_days" name="daily_allowance_days" type="number" min="1" value="{{ old('daily_allowance_days', $isEdit ? $entry->daily_allowance_days : '') }}" data-multiply-left="daily" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                </div>
                                <div>
                                    <label for="daily_allowance_rate" class="block text-sm font-medium text-slate-700">Uang Harian</label>
                                    <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm">
                                        <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                        <input id="daily_allowance_rate" name="daily_allowance_rate" type="text" value="{{ old('daily_allowance_rate', $isEdit ? $entry->daily_allowance_rate : '') }}" data-nominal-input data-multiply-right="daily" class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Total</label>
                                    <input type="text" value="" readonly data-total-output="daily" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-900 outline-none" />
                                </div>
                            </div>
                        </article>

                        <article class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5" data-group>
                            <input type="hidden" name="representation_enabled" value="0" />
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h4 class="text-base font-semibold text-slate-900">Representasi</h4>
                                </div>
                                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                                    <input type="checkbox" name="representation_enabled" value="1" @checked(old('representation_enabled', $isEdit ? $entry->representation_enabled : false)) data-group-toggle class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                                    Aktif
                                </label>
                            </div>
                            <div class="mt-5 grid gap-5 md:grid-cols-3" data-group-body>
                                <div>
                                    <label for="representation_days" class="block text-sm font-medium text-slate-700">Jumlah Hari</label>
                                    <input id="representation_days" name="representation_days" type="number" min="1" value="{{ old('representation_days', $isEdit ? $entry->representation_days : '') }}" data-multiply-left="representation" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                </div>
                                <div>
                                    <label for="representation_rate" class="block text-sm font-medium text-slate-700">Nominal Sesuai SPPD</label>
                                    <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm">
                                        <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                        <input id="representation_rate" name="representation_rate" type="text" value="{{ old('representation_rate', $isEdit ? $entry->representation_rate : '') }}" data-nominal-input data-multiply-right="representation" class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Total</label>
                                    <input type="text" value="" readonly data-total-output="representation" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-900 outline-none" />
                                </div>
                            </div>
                        </article>

                        <article class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5" data-group>
                            <input type="hidden" name="ticket_enabled" value="0" />
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h4 class="text-base font-semibold text-slate-900">Tiket</h4>
                                </div>
                                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                                    <input type="checkbox" name="ticket_enabled" value="1" @checked(old('ticket_enabled', $isEdit ? $entry->ticket_enabled : false)) data-group-toggle class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                                    Aktif
                                </label>
                            </div>
                            <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-4" data-group-body>
                                <div>
                                    <label for="ticket_transport_type" class="block text-sm font-medium text-slate-700">Pesawat / Kapal / Speed / Dll</label>
                                    <select id="ticket_transport_type" name="ticket_transport_type" data-transport-type class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                        <option value="">Pilih transport</option>
                                        @foreach ($transportTypes as $transportType)
                                            <option value="{{ $transportType }}" @selected(old('ticket_transport_type', $isEdit ? $entry->ticket_transport_type : '') === $transportType)>{{ $transportType }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="ticket_departure_date" class="block text-sm font-medium text-slate-700">Tanggal Berangkat</label>
                                    <input id="ticket_departure_date" name="ticket_departure_date" type="date" value="{{ old('ticket_departure_date', $isEdit && $entry->ticket_departure_date ? $entry->ticket_departure_date->format('Y-m-d') : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                </div>
                                <div>
                                    <label for="ticket_return_date" class="block text-sm font-medium text-slate-700">Tanggal Pulang</label>
                                    <input id="ticket_return_date" name="ticket_return_date" type="date" value="{{ old('ticket_return_date', $isEdit && $entry->ticket_return_date ? $entry->ticket_return_date->format('Y-m-d') : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Total</label>
                                    <input type="text" value="" readonly data-total-output="ticket" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-900 outline-none" />
                                </div>
                                <div>
                                    <label for="ticket_departure_price" class="block text-sm font-medium text-slate-700">Harga Tiket Berangkat</label>
                                    <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm">
                                        <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                        <input id="ticket_departure_price" name="ticket_departure_price" type="text" value="{{ old('ticket_departure_price', $isEdit ? $entry->ticket_departure_price : '') }}" data-nominal-input data-sum-ticket="departure" class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                    </div>
                                </div>
                                <div>
                                    <label for="ticket_return_price" class="block text-sm font-medium text-slate-700">Harga Tiket Kembali</label>
                                    <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm">
                                        <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                        <input id="ticket_return_price" name="ticket_return_price" type="text" value="{{ old('ticket_return_price', $isEdit ? $entry->ticket_return_price : '') }}" data-nominal-input data-sum-ticket="return" class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                    </div>
                                </div>
                                <div>
                                    <label for="ticket_departure_operator" class="block text-sm font-medium text-slate-700" data-operator-label="departure">Maskapai Berangkat</label>
                                    <input id="ticket_departure_operator" name="ticket_departure_operator" type="text" value="{{ old('ticket_departure_operator', $isEdit ? $entry->ticket_departure_operator : '') }}" data-operator-input="departure" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                </div>
                                <div>
                                    <label for="ticket_return_operator" class="block text-sm font-medium text-slate-700" data-operator-label="return">Maskapai Kembali</label>
                                    <input id="ticket_return_operator" name="ticket_return_operator" type="text" value="{{ old('ticket_return_operator', $isEdit ? $entry->ticket_return_operator : '') }}" data-operator-input="return" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                </div>
                                <div>
                                    <label for="ticket_departure_number" class="block text-sm font-medium text-slate-700">Nomor Tiket Berangkat</label>
                                    <input id="ticket_departure_number" name="ticket_departure_number" type="text" value="{{ old('ticket_departure_number', $isEdit ? $entry->ticket_departure_number : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                </div>
                                <div>
                                    <label for="ticket_return_number" class="block text-sm font-medium text-slate-700">Nomor Tiket Pulang</label>
                                    <input id="ticket_return_number" name="ticket_return_number" type="text" value="{{ old('ticket_return_number', $isEdit ? $entry->ticket_return_number : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                </div>
                                <div>
                                    <label for="ticket_departure_booking_code" class="block text-sm font-medium text-slate-700">Kode Booking Berangkat</label>
                                    <input id="ticket_departure_booking_code" name="ticket_departure_booking_code" type="text" value="{{ old('ticket_departure_booking_code', $isEdit ? $entry->ticket_departure_booking_code : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                </div>
                                <div>
                                    <label for="ticket_return_booking_code" class="block text-sm font-medium text-slate-700">Kode Booking Pulang</label>
                                    <input id="ticket_return_booking_code" name="ticket_return_booking_code" type="text" value="{{ old('ticket_return_booking_code', $isEdit ? $entry->ticket_return_booking_code : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                </div>
                            </div>
                        </article>

                        <article class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5" data-group>
                            <input type="hidden" name="lodging_enabled" value="0" />
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h4 class="text-base font-semibold text-slate-900">Penginapan</h4>
                                </div>
                                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                                    <input type="checkbox" name="lodging_enabled" value="1" @checked(old('lodging_enabled', $isEdit ? $entry->lodging_enabled : false)) data-group-toggle class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                                    Aktif
                                </label>
                            </div>
                            <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-4" data-group-body>
                                <div>
                                    <label for="lodging_nights" class="block text-sm font-medium text-slate-700">Jumlah Malam</label>
                                    <input id="lodging_nights" name="lodging_nights" type="number" min="1" value="{{ old('lodging_nights', $isEdit ? $entry->lodging_nights : '') }}" data-multiply-left="lodging" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                </div>
                                <div>
                                    <label for="lodging_rate" class="block text-sm font-medium text-slate-700">Nominal Sesuai SPPD</label>
                                    <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm">
                                        <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                        <input id="lodging_rate" name="lodging_rate" type="text" value="{{ old('lodging_rate', $isEdit ? $entry->lodging_rate : '') }}" data-nominal-input data-multiply-right="lodging" class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                    </div>
                                </div>
                                <div class="xl:col-span-2">
                                    <label for="lodging_hotel_name" class="block text-sm font-medium text-slate-700">Nama Hotel</label>
                                    <input id="lodging_hotel_name" name="lodging_hotel_name" type="text" value="{{ old('lodging_hotel_name', $isEdit ? $entry->lodging_hotel_name : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Total</label>
                                    <input type="text" value="" readonly data-total-output="lodging" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-900 outline-none" />
                                </div>
                            </div>
                        </article>

                        <article class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5" data-group>
                            <input type="hidden" name="local_transport_enabled" value="0" />
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h4 class="text-base font-semibold text-slate-900">Transportasi Lokal</h4>
                                </div>
                                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                                    <input type="checkbox" name="local_transport_enabled" value="1" @checked(old('local_transport_enabled', $isEdit ? $entry->local_transport_enabled : false)) data-group-toggle class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                                    Aktif
                                </label>
                            </div>
                            <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3" data-group-body>
                                @php
                                    $localFields = [
                                        'local_transport_domicile_to_airport' => 'Domisili ke Bandara',
                                        'local_transport_airport_to_domicile' => 'Bandara ke Domisili',
                                        'local_transport_airport_to_hotel' => 'Bandara ke Hotel',
                                        'local_transport_hotel_to_airport' => 'Hotel ke Bandara',
                                        'local_transport_other' => 'Lain-lain',
                                    ];
                                @endphp
                                @foreach ($localFields as $field => $label)
                                    <div>
                                        <label for="{{ $field }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
                                        <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm">
                                            <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                            <input id="{{ $field }}" name="{{ $field }}" type="text" value="{{ old($field, $isEdit ? $entry->{$field} : '') }}" data-nominal-input data-local-transport class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                        </div>
                                    </div>
                                @endforeach
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Total</label>
                                    <input type="text" value="" readonly data-total-output="local_transport" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-900 outline-none" />
                                </div>
                            </div>
                        </article>

                        <article class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5" data-group>
                            <input type="hidden" name="other_cost_enabled" value="0" />
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <h4 class="text-base font-semibold text-slate-900">Biaya Lain-lain</h4>
                                </div>
                                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                                    <input type="checkbox" name="other_cost_enabled" value="1" @checked(old('other_cost_enabled', $isEdit ? $entry->other_cost_enabled : false)) data-group-toggle class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                                    Aktif
                                </label>
                            </div>
                            <div class="mt-5 grid gap-5 md:max-w-md" data-group-body>
                                <div>
                                    <label for="other_cost_amount" class="block text-sm font-medium text-slate-700">Biaya Lain-lain</label>
                                    <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm">
                                        <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                        <input id="other_cost_amount" name="other_cost_amount" type="text" value="{{ old('other_cost_amount', $isEdit ? $entry->other_cost_amount : '') }}" data-nominal-input data-other-cost class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div class="rounded-3xl border border-emerald-200 bg-emerald-50/60 p-5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-medium text-emerald-700">Grand Total</p>
                                <p class="text-sm text-slate-500">Total otomatis dari semua komponen yang aktif.</p>
                            </div>
                            <input type="text" value="" readonly data-total-output="grand_total" class="w-full rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-lg font-semibold text-emerald-700 outline-none sm:w-72" />
                        </div>
                    </div>
                </section>

                <section class="space-y-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-violet-100 text-sm font-semibold text-violet-700">04</div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Dokumentasi</h3>
                            <p class="text-sm text-slate-500">Unggah PDF kegiatan dan bukti nota atau tiket.</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="activity_file" class="block text-sm font-medium text-slate-700">Kegiatan</label>
                            <input id="activity_file" name="activity_file" type="file" accept="application/pdf" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm file:mr-4 file:rounded-full file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white" />
                            <p class="mt-2 text-xs text-slate-500">Upload file PDF dengan ukuran kecil.</p>
                            @if ($isEdit && $entry->activity_file_path)
                                <a href="{{ route('perjadin.attachments.show', [$entry, 'activity']) }}" target="_blank" class="mt-2 inline-block text-sm font-medium text-sky-700 hover:text-sky-900 hover:underline">
                                    {{ $entry->activity_file_original_name ?: 'Lihat PDF kegiatan saat ini' }}
                                </a>
                            @endif
                        </div>
                        <div>
                            <label for="receipt_file" class="block text-sm font-medium text-slate-700">Bukti Nota / Tiket</label>
                            <input id="receipt_file" name="receipt_file" type="file" accept="application/pdf" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm file:mr-4 file:rounded-full file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white" />
                            <p class="mt-2 text-xs text-slate-500">Upload file PDF dengan ukuran kecil.</p>
                            @if ($isEdit && $entry->receipt_file_path)
                                <a href="{{ route('perjadin.attachments.show', [$entry, 'receipt']) }}" target="_blank" class="mt-2 inline-block text-sm font-medium text-sky-700 hover:text-sky-900 hover:underline">
                                    {{ $entry->receipt_file_original_name ?: 'Lihat PDF nota / tiket saat ini' }}
                                </a>
                            @endif
                        </div>
                    </div>
                </section>

                <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
                    <a href="{{ route('perjadin', ['month' => $currentPeriod['month'], 'year' => $currentPeriod['year'], 'category' => $activeCategory, 'keyword' => $activeKeyword]) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700">
                        {{ $isEdit ? 'Perbarui Perjadin' : 'Simpan Perjadin' }}
                    </button>
                </div>
            </form>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const digitsOnly = (value) => (value || '').toString().replace(/\D/g, '');
            const toNumber = (value) => Number(digitsOnly(value) || 0);
            const formatNominal = (value) => new Intl.NumberFormat('id-ID').format(Number(value || 0));

            const multiplyPairs = [
                ['daily', '[data-multiply-left="daily"]', '[data-multiply-right="daily"]'],
                ['representation', '[data-multiply-left="representation"]', '[data-multiply-right="representation"]'],
                ['lodging', '[data-multiply-left="lodging"]', '[data-multiply-right="lodging"]'],
            ];

            const ticketDeparture = document.querySelector('[data-sum-ticket="departure"]');
            const ticketReturn = document.querySelector('[data-sum-ticket="return"]');
            const localTransportInputs = document.querySelectorAll('[data-local-transport]');
            const otherCostInput = document.querySelector('[data-other-cost]');
            const transportTypeInput = document.querySelector('[data-transport-type]');
            const operatorLabels = document.querySelectorAll('[data-operator-label]');
            const operatorInputs = document.querySelectorAll('[data-operator-input]');
            const groupToggles = document.querySelectorAll('[data-group-toggle]');

            const output = (key) => document.querySelector(`[data-total-output="${key}"]`);

            const labelMap = {
                Pesawat: 'Maskapai',
                Kapal: 'Nama Kapal',
                Speed: 'Nama Speed',
                Kereta: 'Nama Kereta',
                Bus: 'Nama Bus',
                Lainnya: 'Operator Transport',
            };

            const isEnabled = (name) => !!document.querySelector(`input[name="${name}"][type="checkbox"]`)?.checked;

            const updateOperatorLabels = () => {
                const transportType = transportTypeInput?.value || 'Pesawat';
                const operatorLabel = labelMap[transportType] || 'Operator Transport';

                operatorLabels.forEach((label) => {
                    const type = label.dataset.operatorLabel === 'departure' ? 'Berangkat' : 'Kembali';
                    label.textContent = `${operatorLabel} ${type}`;
                });

                operatorInputs.forEach((input) => {
                    const type = input.dataset.operatorInput === 'departure' ? 'berangkat' : 'kembali';
                    input.placeholder = `${operatorLabel} ${type}`;
                });
            };

            const updateGroupPanels = () => {
                groupToggles.forEach((toggle) => {
                    const group = toggle.closest('[data-group]');
                    const body = group?.querySelector('[data-group-body]');

                    if (!body) {
                        return;
                    }

                    body.classList.toggle('hidden', !toggle.checked);
                    group.classList.toggle('border-sky-200', toggle.checked);
                    group.classList.toggle('bg-sky-50/40', toggle.checked);
                });
            };

            const calculateTotals = () => {
                let grandTotal = 0;

                multiplyPairs.forEach(([key, leftSelector, rightSelector]) => {
                    const left = Number(document.querySelector(leftSelector)?.value || 0);
                    const right = toNumber(document.querySelector(rightSelector)?.value || 0);
                    const total = isEnabled(`${key === 'daily' ? 'daily_allowance' : key}_enabled`) ? left * right : 0;
                    grandTotal += total;

                    const target = output(key);
                    if (target) {
                        target.value = total > 0 ? `Rp ${formatNominal(total)}` : '';
                    }
                });

                const ticketTotal = isEnabled('ticket_enabled')
                    ? toNumber(ticketDeparture?.value) + toNumber(ticketReturn?.value)
                    : 0;
                grandTotal += ticketTotal;
                if (output('ticket')) {
                    output('ticket').value = ticketTotal > 0 ? `Rp ${formatNominal(ticketTotal)}` : '';
                }

                const localTotal = isEnabled('local_transport_enabled')
                    ? Array.from(localTransportInputs).reduce((carry, input) => carry + toNumber(input.value), 0)
                    : 0;
                grandTotal += localTotal;
                if (output('local_transport')) {
                    output('local_transport').value = localTotal > 0 ? `Rp ${formatNominal(localTotal)}` : '';
                }

                const otherTotal = isEnabled('other_cost_enabled') ? toNumber(otherCostInput?.value) : 0;
                grandTotal += otherTotal;

                if (output('grand_total')) {
                    output('grand_total').value = grandTotal > 0 ? `Rp ${formatNominal(grandTotal)}` : 'Rp 0';
                }
            };

            document.querySelectorAll('input, select').forEach((field) => {
                field.addEventListener('input', calculateTotals);
                field.addEventListener('change', () => {
                    updateGroupPanels();
                    updateOperatorLabels();
                    calculateTotals();
                });
            });

            updateGroupPanels();
            updateOperatorLabels();
            calculateTotals();
        });
    </script>
    <x-nominal-input-script />
</x-layout>
