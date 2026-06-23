<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    @php
        $isEdit = isset($entry) && $entry !== null;
        $selectedOriginRegency = old('origin_regency', $isEdit ? $entry->origin_regency : '');
        $selectedOriginDistrict = old('origin_district', $isEdit ? $entry->origin_district : '');
        $selectedDestinationRegency = old('destination_regency', $isEdit ? $entry->destination_regency : '');
        $selectedDestinationDistrict = old('destination_district', $isEdit ? $entry->destination_district : '');
        $originDistrictChoices = $originDistrictOptions[$selectedOriginRegency] ?? [];
        $destinationDistrictChoices = $destinationDistrictOptions[$selectedDestinationRegency] ?? [];
        $selectedCategoryValue = old('category', $isEdit ? $entry->category : $activeCategory);
        $showRegionalRouteFields = $selectedCategoryValue === 'Perjadin Dalam Daerah';
        $selectedOutsideDestination = old('destination_city', $isEdit ? $entry->destination_city : '');
        $selectedLocalTransportSegmentIds = old('local_transport_segment_ids', $isEdit ? ($entry->local_transport_segment_ids ?? []) : []);
        $inlineFieldLabels = [
            'category' => 'kategori perjadin',
            'origin_regency' => 'kabupaten asal',
            'origin_district' => 'kecamatan asal',
            'destination_regency' => 'kabupaten tujuan',
            'destination_district' => 'kecamatan tujuan',
            'destination_city' => 'kota / kabupaten tujuan',
            'regional_trip_scope' => 'jenis perjalanan dalam daerah',
            'skpd_name' => 'nama SKPD',
            'executor_name' => 'nama pelaksana',
            'position_name' => 'jabatan',
            'echelon_level' => 'eselon',
            'grade' => 'golongan',
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal selesai',
            'assignment_number' => 'nomor surat tugas',
            'assignment_date' => 'tanggal surat tugas',
            'signature_location' => 'lokasi tanda tangan',
            'daily_allowance_days' => 'jumlah hari uang harian',
            'daily_allowance_rate' => 'nominal uang harian',
            'representation_days' => 'jumlah hari representasi',
            'representation_rate' => 'nominal representasi',
            'ticket_transport_type' => 'jenis transport tiket',
            'ticket_departure_date' => 'tanggal berangkat tiket',
            'ticket_return_date' => 'tanggal pulang tiket',
            'ticket_departure_price' => 'harga tiket berangkat',
            'ticket_return_price' => 'harga tiket kembali',
            'lodging_nights' => 'jumlah malam penginapan',
            'lodging_rate' => 'nominal penginapan',
        ];
        $inlineError = function (string ...$names) use ($errors, $inlineFieldLabels) {
            foreach ($names as $name) {
                if (! $errors->has($name)) {
                    continue;
                }

                $message = $errors->first($name);
                $label = $inlineFieldLabels[$name] ?? str_replace('_', ' ', $name);

                if (str_contains(strtolower($message), 'required')) {
                    return 'Kolom '.ucfirst($label).' masih perlu diisi.';
                }

                return $message;
            }

            return null;
        };
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

            <form id="perjadin-form" action="{{ $isEdit ? route('perjadin.update', $entry) : route('add-perjadin.store') }}" method="POST" enctype="multipart/form-data" class="mt-8 space-y-8">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <section class="space-y-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-900 text-sm font-semibold text-white">00</div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Kategori dan Wilayah Perjalanan</h3>
                            <p class="text-sm text-slate-500">Kategori selalu dipilih lebih dulu, lalu wilayah asal dan tujuan muncul khusus untuk perjadin dalam daerah.</p>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                            <label for="category" class="block text-sm font-medium text-slate-700">Kategori</label>
                            <select id="category" name="category" onchange="window.updatePerjadinRegionalFields && window.updatePerjadinRegionalFields(this.value)" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                <option value="">Pilih kategori perjadin</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}" @selected($selectedCategoryValue === $category)>{{ $category }}</option>
                                @endforeach
                            </select>
                            @if($message = $inlineError('category'))
                                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                            @endif
                            <p class="mt-3 text-xs text-slate-500">Saat memilih <span class="font-semibold text-slate-700">Perjadin Dalam Daerah</span>, form asal dan tujuan akan muncul otomatis di bawah ini.</p>
                        </div>

                        <div id="destination-city-sbu-wrapper" class="{{ $showRegionalRouteFields ? 'hidden' : '' }} rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-medium text-sky-600">Tujuan Luar Daerah</p>
                                </div>
                                <span class="rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-medium uppercase tracking-[0.18em] text-sky-700">Tujuan</span>
                            </div>
                            <div class="mt-4">
                                <label for="destination_city_select" class="block text-sm font-medium text-slate-700">Kota / Kab Tujuan</label>
                                <select id="destination_city_select" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                    <option value="">Pilih tujuan luar daerah dari acuan SBU</option>
                                    @foreach ($outsideDestinationOptions as $option)
                                        <option value="{{ $option['value'] }}" @selected($selectedOutsideDestination === $option['value'])>{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                                <input id="destination_city" name="destination_city" type="hidden" value="{{ $selectedOutsideDestination }}" />
                                @if($message = $inlineError('destination_city'))
                                    <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                @endif
                                <p class="mt-2 text-xs text-slate-500">Daftar tujuan luar daerah diambil langsung dari master SBU agar acuan uang harian, representasi, penginapan, tiket, dan taksi bandara bisa tersinkron otomatis.</p>
                            </div>
                            
                        </div>

                        <div id="regional-route-fields" class="space-y-5 {{ $showRegionalRouteFields ? '' : 'hidden' }}">
                            <div class="grid gap-5 xl:grid-cols-2">
                                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-medium text-amber-600">Blok Asal</p>
                                            <h4 class="mt-1 text-base font-semibold text-slate-900">Wilayah keberangkatan</h4>
                                        </div>
                                        <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-medium uppercase tracking-[0.18em] text-amber-700">Asal</span>
                                    </div>
                                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                                        <div>
                                            <label for="origin_regency" class="block text-sm font-medium text-slate-700">Kabupaten Asal</label>
                                            <select id="origin_regency" name="origin_regency" data-regency-target="origin" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                                <option value="">Pilih kabupaten asal</option>
                                                @foreach ($regencyOptions as $regency)
                                                    <option value="{{ $regency }}" @selected($selectedOriginRegency === $regency)>{{ $regency }}</option>
                                                @endforeach
                                            </select>
                                            @if($message = $inlineError('origin_regency'))
                                                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                            @endif
                                        </div>
                                        <div>
                                            <label for="origin_district" class="block text-sm font-medium text-slate-700">Kecamatan Asal</label>
                                            <select id="origin_district" name="origin_district" data-district-target="origin" data-selected="{{ $selectedOriginDistrict }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                                <option value="">Pilih kecamatan asal</option>
                                                @foreach ($originDistrictChoices as $district)
                                                    <option value="{{ $district }}" @selected($selectedOriginDistrict === $district)>{{ $district }}</option>
                                                @endforeach
                                            </select>
                                            @if($message = $inlineError('origin_district'))
                                                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-medium text-sky-600">Blok Tujuan</p>
                                            <h4 class="mt-1 text-base font-semibold text-slate-900">Wilayah perjalanan dinas</h4>
                                        </div>
                                        <span class="rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-medium uppercase tracking-[0.18em] text-sky-700">Tujuan</span>
                                    </div>
                                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                                        <div>
                                            <label for="destination_regency" class="block text-sm font-medium text-slate-700">Kabupaten Tujuan</label>
                                            <select id="destination_regency" name="destination_regency" data-regency-target="destination" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                                <option value="">Pilih kabupaten tujuan</option>
                                                @foreach ($regencyOptions as $regency)
                                                    <option value="{{ $regency }}" @selected($selectedDestinationRegency === $regency)>{{ $regency }}</option>
                                                @endforeach
                                            </select>
                                            @if($message = $inlineError('destination_regency'))
                                                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                            @endif
                                        </div>
                                        <div>
                                            <label for="destination_district" class="block text-sm font-medium text-slate-700">Kecamatan Tujuan</label>
                                            <select id="destination_district" name="destination_district" data-district-target="destination" data-selected="{{ $selectedDestinationDistrict }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                                <option value="">Pilih kecamatan tujuan</option>
                                                @foreach ($destinationDistrictChoices as $district)
                                                    <option value="{{ $district }}" @selected($selectedDestinationDistrict === $district)>{{ $district }}</option>
                                                @endforeach
                                            </select>
                                            @if($message = $inlineError('destination_district'))
                                                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="local-transport-reference-panel" class="rounded-3xl border border-sky-200 bg-sky-50/60 p-5">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-sky-700">Acuan SBU Transport Lokal</p>
                                        <p id="local-transport-reference-state" class="mt-2 text-sm text-slate-600">Pilih kabupaten tujuan, lalu operator dapat menambahkan segment rute satu per satu dari master SBU transport lokal.</p>
                                    </div>
                                    <button id="add-local-transport-segment" type="button" class="inline-flex items-center justify-center rounded-2xl border border-sky-300 bg-white px-4 py-2.5 text-sm font-medium text-sky-700 transition hover:border-sky-400 hover:bg-sky-50">
                                        Tambah Rute / Segment
                                    </button>
                                </div>
                                <div id="local-transport-segment-empty" class="mt-4 rounded-2xl border border-dashed border-sky-200 bg-white/80 px-4 py-3 text-sm text-slate-500">
                                    Belum ada segment dipilih. Tambahkan rute sesuai tahapan perjalanan untuk menghitung total acuan SBU transport lokal.
                                </div>
                                <div id="local-transport-segment-list" class="mt-4 space-y-3"></div>
                                <div id="local-transport-reference-highlight" class="mt-4 hidden rounded-2xl border border-emerald-200 bg-white px-4 py-4 text-sm text-slate-700"></div>
                            </div>
                        </div>
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

                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-5">
                        <div class="xl:col-span-2">
                            <label for="skpd_name" class="block text-sm font-medium text-slate-700">Nama SKPD</label>
                            <input id="skpd_name" name="skpd_name" type="text" value="{{ old('skpd_name', $isEdit ? $entry->skpd_name : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            @if($message = $inlineError('skpd_name'))
                                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                            @endif
                        </div>
                        <div class="xl:col-span-2">
                            <label for="executor_name" class="block text-sm font-medium text-slate-700">Nama Pelaksana</label>
                            <input id="executor_name" name="executor_name" type="text" value="{{ old('executor_name', $isEdit ? $entry->executor_name : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            @if($message = $inlineError('executor_name'))
                                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                            @endif
                        </div>
                        <div class="xl:col-span-2">
                            <label for="position_name" class="block text-sm font-medium text-slate-700">Jabatan</label>
                            <input id="position_name" name="position_name" type="text" value="{{ old('position_name', $isEdit ? $entry->position_name : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            @if($message = $inlineError('position_name'))
                                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                            @endif
                        </div>
                        <div>
                            <label for="echelon_level" class="block text-sm font-medium text-slate-700">Eselon</label>
                            <select id="echelon_level" name="echelon_level" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                <option value="">Pilih eselon</option>
                                @foreach ($echelonOptions as $echelon)
                                    <option value="{{ $echelon }}" @selected(old('echelon_level', $isEdit ? $entry->echelon_level : '') === $echelon)>{{ $echelon }}</option>
                                @endforeach
                            </select>
                            @if($message = $inlineError('echelon_level'))
                                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                            @endif
                        </div>
                        <div>
                            <label for="grade" class="block text-sm font-medium text-slate-700">Golongan</label>
                            <select id="grade" name="grade" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                <option value="">Pilih golongan</option>
                                @foreach ($gradeOptions as $grade)
                                    <option value="{{ $grade }}" @selected(old('grade', $isEdit ? $entry->grade : '') === $grade)>{{ $grade }}</option>
                                @endforeach
                            </select>
                            @if($message = $inlineError('grade'))
                                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                            @endif
                        </div>
                    </div>
                </section>

                <section class="space-y-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-100 text-sm font-semibold text-sky-700">02</div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Jangka Waktu Surat Perintah Tugas</h3>
                            <p class="text-sm text-slate-500">Lengkapi tanggal dan informasi surat tugas.</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-6">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-slate-700">Dari</label>
                            <input id="start_date" name="start_date" type="date" value="{{ old('start_date', $isEdit && $entry->start_date ? $entry->start_date->format('Y-m-d') : $defaultStartDate) }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            @if($message = $inlineError('start_date'))
                                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                            @endif
                        </div>
                        <div>
                            <label for="end_date" class="block text-sm font-medium text-slate-700">Sampai</label>
                            <input id="end_date" name="end_date" type="date" value="{{ old('end_date', $isEdit && $entry->end_date ? $entry->end_date->format('Y-m-d') : $defaultStartDate) }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            @if($message = $inlineError('end_date'))
                                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                            @endif
                        </div>
                        <div class="xl:col-span-2">
                            <label for="assignment_number" class="block text-sm font-medium text-slate-700">No Surat Tugas</label>
                            <input id="assignment_number" name="assignment_number" type="text" value="{{ old('assignment_number', $isEdit ? $entry->assignment_number : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            @if($message = $inlineError('assignment_number'))
                                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                            @endif
                        </div>
                        <div class="xl:col-span-1">
                            <label for="assignment_date" class="block text-sm font-medium text-slate-700">Tanggal Surat Tugas</label>
                            <input id="assignment_date" name="assignment_date" type="date" value="{{ old('assignment_date', $isEdit && $entry->assignment_date ? $entry->assignment_date->format('Y-m-d') : $defaultStartDate) }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            @if($message = $inlineError('assignment_date'))
                                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                            @endif
                        </div>
                        <div class="xl:col-span-1">
                            <label for="signature_location" class="block text-sm font-medium text-slate-700">Lokasi TTD</label>
                            <input id="signature_location" name="signature_location" type="text" value="{{ old('signature_location', $isEdit ? $entry->signature_location : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            @if($message = $inlineError('signature_location'))
                                <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                            @endif
                        </div>
                        <div id="destination-city-manual-wrapper" class="xl:col-span-6 {{ $showRegionalRouteFields ? '' : 'hidden' }}">
                            <div class="grid gap-5 xl:grid-cols-2">
                                <div>
                                    <label for="destination_city_manual" class="block text-sm font-medium text-slate-700">Kota / Kab Tujuan</label>
                                    <input id="destination_city_manual" type="text" value="{{ old('destination_city', $isEdit ? $entry->destination_city : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                    @if($message = $inlineError('destination_city'))
                                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                    @endif
                                    <p class="mt-2 text-xs text-slate-500">Field ini diisi manual untuk kebutuhan administrasi perjadin dalam daerah.</p>
                                </div>
                                <div>
                                    <label for="regional_trip_scope" class="block text-sm font-medium text-slate-700">Jenis Perjalanan Sofifi</label>
                                    <select id="regional_trip_scope" name="regional_trip_scope" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                        <option value="">Pilih jenis perjalanan</option>
                                        <option value="dalam_kota_sofifi" @selected(old('regional_trip_scope', $isEdit ? $entry->regional_trip_scope : '') === 'dalam_kota_sofifi')>Dalam Kota Sofifi</option>
                                        <option value="luar_kota_sofifi" @selected(old('regional_trip_scope', $isEdit ? $entry->regional_trip_scope : '') === 'luar_kota_sofifi')>Luar Kota Sofifi</option>
                                    </select>
                                    @if($message = $inlineError('regional_trip_scope'))
                                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                    @endif
                                    <p class="mt-2 text-xs text-slate-500">Dipakai untuk membedakan kebutuhan perjalanan dalam daerah dari/ke Sofifi.</p>
                                </div>
                            </div>
                            <div id="sofifi-duration-wrapper" class="mt-4 {{ old('regional_trip_scope', $isEdit ? $entry->regional_trip_scope : '') === 'dalam_kota_sofifi' ? '' : 'hidden' }}">
                                <input type="hidden" name="sofifi_over_8_hours" value="0" />
                                <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                                    <input id="sofifi_over_8_hours" name="sofifi_over_8_hours" type="checkbox" value="1" @checked(old('sofifi_over_8_hours', $isEdit ? $entry->sofifi_over_8_hours : false)) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                                    <span>
                                        <span class="block font-medium text-slate-900">Lebih dari 8 jam</span>
                                        <span class="mt-1 block text-xs leading-5 text-slate-500">Centang jika perjalanan dalam kota Sofifi berlangsung lebih dari 8 jam.</span>
                                    </span>
                                </label>
                            </div>
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
                                    @if($message = $inlineError('daily_allowance_days'))
                                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label for="daily_allowance_rate" class="block text-sm font-medium text-slate-700">Uang Harian</label>
                                    <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm">
                                        <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                        <input id="daily_allowance_rate" name="daily_allowance_rate" type="text" value="{{ old('daily_allowance_rate', $isEdit ? $entry->daily_allowance_rate : '') }}" data-nominal-input data-multiply-right="daily" class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                    </div>
                                    <p id="daily-allowance-sbu-state" class="mt-2 text-xs leading-5 text-slate-500">Nominal uang harian akan menyesuaikan tujuan perjalanan saat kategori luar daerah dipilih.</p>
                                </div>
                                <div>
                                    <label for="daily_total" class="block text-sm font-medium text-slate-700">Total</label>
                                    <input id="daily_total" type="text" value="" readonly data-total-output="daily" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-900 outline-none" />
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
                                    @if($message = $inlineError('representation_days'))
                                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label for="representation_rate" class="block text-sm font-medium text-slate-700">Nominal Sesuai SPPD</label>
                                    <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm">
                                        <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                        <input id="representation_rate" name="representation_rate" type="text" value="{{ old('representation_rate', $isEdit ? $entry->representation_rate : '') }}" data-nominal-input data-multiply-right="representation" class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                    </div>
                                    <p id="representation-sbu-state" class="mt-2 text-xs leading-5 text-slate-500">Nominal representasi akan mengikuti acuan SBU berdasarkan tujuan dan jabatan yang diisi.</p>
                                </div>
                                <div>
                                    <label for="representation_total" class="block text-sm font-medium text-slate-700">Total</label>
                                    <input id="representation_total" type="text" value="" readonly data-total-output="representation" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-900 outline-none" />
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
                                    @if($message = $inlineError('ticket_transport_type'))
                                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label for="ticket_departure_date" class="block text-sm font-medium text-slate-700">Tanggal Berangkat</label>
                                    <input id="ticket_departure_date" name="ticket_departure_date" type="date" value="{{ old('ticket_departure_date', $isEdit && $entry->ticket_departure_date ? $entry->ticket_departure_date->format('Y-m-d') : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                    @if($message = $inlineError('ticket_departure_date'))
                                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label for="ticket_return_date" class="block text-sm font-medium text-slate-700">Tanggal Pulang</label>
                                    <input id="ticket_return_date" name="ticket_return_date" type="date" value="{{ old('ticket_return_date', $isEdit && $entry->ticket_return_date ? $entry->ticket_return_date->format('Y-m-d') : '') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                    @if($message = $inlineError('ticket_return_date'))
                                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label for="ticket_total" class="block text-sm font-medium text-slate-700">Total</label>
                                    <input id="ticket_total" type="text" value="" readonly data-total-output="ticket" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-900 outline-none" />
                                </div>
                                <div>
                                    <label for="ticket_departure_price" class="block text-sm font-medium text-slate-700">Harga Tiket Berangkat</label>
                                    <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm">
                                        <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                        <input id="ticket_departure_price" name="ticket_departure_price" type="text" value="{{ old('ticket_departure_price', $isEdit ? $entry->ticket_departure_price : '') }}" data-nominal-input data-sum-ticket="departure" class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                    </div>
                                    @if($message = $inlineError('ticket_departure_price'))
                                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label for="ticket_return_price" class="block text-sm font-medium text-slate-700">Harga Tiket Kembali</label>
                                    <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm">
                                        <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                        <input id="ticket_return_price" name="ticket_return_price" type="text" value="{{ old('ticket_return_price', $isEdit ? $entry->ticket_return_price : '') }}" data-nominal-input data-sum-ticket="return" class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                    </div>
                                    @if($message = $inlineError('ticket_return_price'))
                                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                    @endif
                                </div>
                                <div class="md:col-span-2 xl:col-span-2">
                                    <p id="ticket-sbu-state" class="mt-2 text-xs leading-5 text-slate-500">
                                        Pilih tujuan luar daerah untuk melihat maksimal SBU tiket sebagai referensi.
                                    </p>
                                </div>
                                <div>
                                    <label for="ticket_departure_operator" class="block text-sm font-medium text-slate-700" data-operator-label="departure">Maskapai Berangkat</label>
                                    <input id="ticket_departure_operator" name="ticket_departure_operator" type="text" value="{{ old('ticket_departure_operator', $isEdit ? $entry->ticket_departure_operator : '') }}" data-operator-input="departure" data-auto-dash-if-empty placeholder="Jika kosong, otomatis -" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                    <p class="mt-2 text-xs text-slate-500">Jika belum ada datanya, boleh dikosongkan. Sistem akan mengisi <span class="font-medium text-slate-700">-</span> otomatis saat disimpan.</p>
                                </div>
                                <div>
                                    <label for="ticket_return_operator" class="block text-sm font-medium text-slate-700" data-operator-label="return">Maskapai Kembali</label>
                                    <input id="ticket_return_operator" name="ticket_return_operator" type="text" value="{{ old('ticket_return_operator', $isEdit ? $entry->ticket_return_operator : '') }}" data-operator-input="return" data-auto-dash-if-empty placeholder="Jika kosong, otomatis -" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                    <p class="mt-2 text-xs text-slate-500">Jika belum ada datanya, boleh dikosongkan. Sistem akan mengisi <span class="font-medium text-slate-700">-</span> otomatis saat disimpan.</p>
                                </div>
                                <div>
                                    <label for="ticket_departure_number" class="block text-sm font-medium text-slate-700">Nomor Tiket Berangkat</label>
                                    <input id="ticket_departure_number" name="ticket_departure_number" type="text" value="{{ old('ticket_departure_number', $isEdit ? $entry->ticket_departure_number : '') }}" data-auto-dash-if-empty placeholder="Jika kosong, otomatis -" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                    <p class="mt-2 text-xs text-slate-500">Jika belum ada datanya, boleh dikosongkan. Sistem akan mengisi <span class="font-medium text-slate-700">-</span> otomatis saat disimpan.</p>
                                </div>
                                <div>
                                    <label for="ticket_return_number" class="block text-sm font-medium text-slate-700">Nomor Tiket Pulang</label>
                                    <input id="ticket_return_number" name="ticket_return_number" type="text" value="{{ old('ticket_return_number', $isEdit ? $entry->ticket_return_number : '') }}" data-auto-dash-if-empty placeholder="Jika kosong, otomatis -" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                    <p class="mt-2 text-xs text-slate-500">Jika belum ada datanya, boleh dikosongkan. Sistem akan mengisi <span class="font-medium text-slate-700">-</span> otomatis saat disimpan.</p>
                                </div>
                                <div>
                                    <label for="ticket_departure_booking_code" class="block text-sm font-medium text-slate-700">Kode Booking Berangkat</label>
                                    <input id="ticket_departure_booking_code" name="ticket_departure_booking_code" type="text" value="{{ old('ticket_departure_booking_code', $isEdit ? $entry->ticket_departure_booking_code : '') }}" data-auto-dash-if-empty placeholder="Jika kosong, otomatis -" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                    <p class="mt-2 text-xs text-slate-500">Jika belum ada datanya, boleh dikosongkan. Sistem akan mengisi <span class="font-medium text-slate-700">-</span> otomatis saat disimpan.</p>
                                </div>
                                <div>
                                    <label for="ticket_return_booking_code" class="block text-sm font-medium text-slate-700">Kode Booking Pulang</label>
                                    <input id="ticket_return_booking_code" name="ticket_return_booking_code" type="text" value="{{ old('ticket_return_booking_code', $isEdit ? $entry->ticket_return_booking_code : '') }}" data-auto-dash-if-empty placeholder="Jika kosong, otomatis -" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                    <p class="mt-2 text-xs text-slate-500">Jika belum ada datanya, boleh dikosongkan. Sistem akan mengisi <span class="font-medium text-slate-700">-</span> otomatis saat disimpan.</p>
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
                                    @if($message = $inlineError('lodging_nights'))
                                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                    @endif
                                </div>
                                <div class="md:col-span-2 xl:col-span-3">
                                    <input type="hidden" name="lodging_has_receipt" value="0" />
                                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                                        <input id="lodging_has_receipt" name="lodging_has_receipt" type="checkbox" value="1" @checked(old('lodging_has_receipt', $isEdit ? $entry->lodging_has_receipt : true)) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                                        <span>
                                            <span class="block font-medium text-slate-900">Ada Nota / Bill Hotel</span>
                                            <span class="mt-1 block text-xs leading-5 text-slate-500">Jika dipilih, nominal penginapan memakai full SBU. Jika tidak, nominal otomatis dihitung lumpsum setinggi-tingginya 30% dari tarif penginapan di kota tujuan.</span>
                                        </span>
                                    </label>
                                </div>
                                <div>
                                    <label for="lodging_rate" class="block text-sm font-medium text-slate-700">Nominal Penginapan Berlaku</label>
                                    <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm">
                                        <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                        <input id="lodging_rate" name="lodging_rate" type="text" value="{{ old('lodging_rate', $isEdit ? $entry->lodging_rate : '') }}" data-nominal-input data-multiply-right="lodging" class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                    </div>
                                    @if($message = $inlineError('lodging_rate'))
                                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                    @endif
                                    <p id="lodging-sbu-state" class="mt-2 text-xs leading-5 text-slate-500">Nominal penginapan akan mengikuti acuan SBU sesuai tujuan, eselon, dan golongan yang berlaku.</p>
                                </div>
                                <div class="xl:col-span-2">
                                    <label for="lodging_hotel_name" class="block text-sm font-medium text-slate-700">Nama Hotel</label>
                                    <input id="lodging_hotel_name" name="lodging_hotel_name" type="text" value="{{ old('lodging_hotel_name', $isEdit ? $entry->lodging_hotel_name : '') }}" data-auto-dash-if-empty placeholder="Jika kosong, otomatis -" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                    <p class="mt-2 text-xs text-slate-500">Jika belum ada datanya, boleh dikosongkan. Sistem akan mengisi <span class="font-medium text-slate-700">-</span> otomatis saat disimpan.</p>
                                </div>
                                <div>
                                    <label for="lodging_total" class="block text-sm font-medium text-slate-700">Total</label>
                                    <input id="lodging_total" type="text" value="" readonly data-total-output="lodging" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-900 outline-none" />
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
                                    <div data-local-transport-field-wrapper data-local-transport-field="{{ $field }}">
                                        <label for="{{ $field }}" data-local-transport-label="{{ $field }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
                                        <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm">
                                            <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                            <input id="{{ $field }}" name="{{ $field }}" type="text" value="{{ old($field, $isEdit ? $entry->{$field} : '') }}" data-nominal-input data-local-transport class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                        </div>
                                    </div>
                                @endforeach
                                <div class="md:col-span-2 xl:col-span-3">
                                    <p id="local-transport-sbu-state" class="mt-2 text-xs leading-5 text-slate-500">
                                        Transport lokal luar daerah akan mengambil acuan Taksi Bandara dari master SBU.
                                    </p>
                                </div>
                                <div>
                                    <label for="local_transport_total" class="block text-sm font-medium text-slate-700">Total</label>
                                    <input id="local_transport_total" type="text" value="" readonly data-total-output="local_transport" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-900 outline-none" />
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
                            <input id="grand_total" type="text" value="" readonly data-total-output="grand_total" class="w-full rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-lg font-semibold text-emerald-700 outline-none sm:w-72" />
                        </div>

                        <div id="sbu-comparison-panel" class="mt-4 rounded-3xl border border-slate-200 bg-white/90 p-4 shadow-sm">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-sm font-medium text-slate-700">Perbandingan Total SBU</p>
                                    <p id="sbu-comparison-state" class="mt-1 text-sm text-slate-500">Aktifkan komponen biaya untuk melihat total acuan SBU.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-right">
                                    <p class="text-[11px] font-medium uppercase tracking-[0.2em] text-slate-400">Total Acuan SBU</p>
                                    <p id="sbu-comparison-total" class="mt-1 text-base font-semibold text-slate-900">Rp 0</p>
                                </div>
                            </div>
                            <div id="sbu-comparison-rows" class="mt-4 space-y-2"></div>
                        </div>
                    </div>
                </section>

                <section class="space-y-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-violet-100 text-sm font-semibold text-violet-700">04</div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Dokumentasi</h3>
                            <p class="text-sm text-slate-500">Unggah PDF foto kegiatan, bukti nota atau tiket, dan laporan perjadin.</p>
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-3">
                        <div>
                            <label for="activity_file" class="block text-sm font-medium text-slate-700">Foto Kegiatan</label>
                            <input id="activity_file" name="activity_file" type="file" accept="application/pdf" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm file:mr-4 file:rounded-full file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white" />
                            <p class="mt-2 text-xs text-slate-500">Upload file PDF dengan ukuran kecil.</p>
                            @if ($isEdit && $entry->activity_file_path)
                                <div id="activity-current-wrapper" class="mt-2 space-y-2">
                                    <a href="{{ route('perjadin.attachments.show', [$entry, 'activity']) }}" target="_blank" class="inline-block text-sm font-medium text-sky-700 hover:text-sky-900 hover:underline">
                                        {{ $entry->activity_file_original_name ?: 'Lihat PDF foto kegiatan saat ini' }}
                                    </a>
                                    <label class="flex items-center gap-2 text-xs text-rose-600">
                                        <input id="remove_activity_file" name="remove_activity_file" type="checkbox" value="1" @checked(old('remove_activity_file')) class="h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500" />
                                        Hapus file foto kegiatan saat ini
                                    </label>
                                </div>
                            @endif
                        </div>
                        <div>
                            <label for="receipt_file" class="block text-sm font-medium text-slate-700">Bukti Nota / Tiket</label>
                            <input id="receipt_file" name="receipt_file" type="file" accept="application/pdf" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm file:mr-4 file:rounded-full file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white" />
                            <p class="mt-2 text-xs text-slate-500">Upload file PDF dengan ukuran kecil.</p>
                            @if ($isEdit && $entry->receipt_file_path)
                                <div id="receipt-current-wrapper" class="mt-2 space-y-2">
                                    <a href="{{ route('perjadin.attachments.show', [$entry, 'receipt']) }}" target="_blank" class="inline-block text-sm font-medium text-sky-700 hover:text-sky-900 hover:underline">
                                        {{ $entry->receipt_file_original_name ?: 'Lihat PDF nota / tiket saat ini' }}
                                    </a>
                                    <label class="flex items-center gap-2 text-xs text-rose-600">
                                        <input id="remove_receipt_file" name="remove_receipt_file" type="checkbox" value="1" @checked(old('remove_receipt_file')) class="h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500" />
                                        Hapus file nota / tiket saat ini
                                    </label>
                                </div>
                            @endif
                        </div>
                        <div>
                            <label for="report_file" class="block text-sm font-medium text-slate-700">Laporan Perjadin</label>
                            <input id="report_file" name="report_file" type="file" accept="application/pdf" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm file:mr-4 file:rounded-full file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white" />
                            <p class="mt-2 text-xs text-slate-500">Upload file PDF dengan ukuran kecil.</p>
                            @if ($isEdit && $entry->report_file_path)
                                <div id="report-current-wrapper" class="mt-2 space-y-2">
                                    <a href="{{ route('perjadin.attachments.show', [$entry, 'report']) }}" target="_blank" class="inline-block text-sm font-medium text-sky-700 hover:text-sky-900 hover:underline">
                                        {{ $entry->report_file_original_name ?: 'Lihat PDF laporan perjadin saat ini' }}
                                    </a>
                                    <label class="flex items-center gap-2 text-xs text-rose-600">
                                        <input id="remove_report_file" name="remove_report_file" type="checkbox" value="1" @checked(old('remove_report_file')) class="h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500" />
                                        Hapus file laporan perjadin saat ini
                                    </label>
                                </div>
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
            const moneyLabel = (value) => `Rp ${formatNominal(value)}`;
            const escapeHtml = (value) => (value || '').toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
            const normalizeText = (value) => (value || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, ' ').trim();
            const sameNormalized = (left, right) => normalizeText(left) === normalizeText(right);

            const multiplyPairs = [
                ['daily', '[data-multiply-left="daily"]', '[data-multiply-right="daily"]', 'daily_allowance_enabled'],
                ['representation', '[data-multiply-left="representation"]', '[data-multiply-right="representation"]', 'representation_enabled'],
                ['lodging', '[data-multiply-left="lodging"]', '[data-multiply-right="lodging"]', 'lodging_enabled'],
            ];

            const ticketDeparture = document.querySelector('[data-sum-ticket="departure"]');
            const ticketReturn = document.querySelector('[data-sum-ticket="return"]');
            const localTransportInputs = document.querySelectorAll('[data-local-transport]');
            const otherCostInput = document.querySelector('[data-other-cost]');
            const transportTypeInput = document.querySelector('[data-transport-type]');
            const operatorLabels = document.querySelectorAll('[data-operator-label]');
            const operatorInputs = document.querySelectorAll('[data-operator-input]');
            const groupToggles = document.querySelectorAll('[data-group-toggle]');
            const categoryInput = document.getElementById('category');
            const regionalRouteFields = document.getElementById('regional-route-fields');
            const destinationCityManualWrapper = document.getElementById('destination-city-manual-wrapper');
            const destinationCityManualInput = document.getElementById('destination_city_manual');
            const regionalTripScopeInput = document.getElementById('regional_trip_scope');
            const sofifiDurationWrapper = document.getElementById('sofifi-duration-wrapper');
            const sofifiOver8HoursInput = document.getElementById('sofifi_over_8_hours');
            const destinationCitySbuWrapper = document.getElementById('destination-city-sbu-wrapper');
            const originRegencyInput = document.getElementById('origin_regency');
            const originDistrictInput = document.getElementById('origin_district');
            const destinationRegencyInput = document.getElementById('destination_regency');
            const destinationDistrictInput = document.getElementById('destination_district');
            const originDistrictOptions = @json($originDistrictOptions);
            const destinationDistrictOptions = @json($destinationDistrictOptions);
            const localTransportReferences = @json($localTransportReferences);
            const initialLocalTransportSegmentIds = @json(array_values($selectedLocalTransportSegmentIds));
            const localTransportReferencePanel = document.getElementById('local-transport-reference-panel');
            const localTransportReferenceState = document.getElementById('local-transport-reference-state');
            const localTransportReferenceHighlight = document.getElementById('local-transport-reference-highlight');
            const localTransportSegmentList = document.getElementById('local-transport-segment-list');
            const localTransportSegmentEmpty = document.getElementById('local-transport-segment-empty');
            const addLocalTransportSegmentButton = document.getElementById('add-local-transport-segment');
            const destinationCityInput = document.getElementById('destination_city');
            const destinationCitySelect = document.getElementById('destination_city_select');
            const outsideDestinationOptions = @json($outsideDestinationOptions);
            const outsideRegionSbuSummary = document.getElementById('outside-region-sbu-summary');
            const outsideRegionSbuSummaryContent = document.getElementById('outside-region-sbu-summary-content');
            const echelonInput = document.getElementById('echelon_level');
            const gradeInput = document.getElementById('grade');
            const positionInput = document.getElementById('position_name');
            const airportTaxiReference = @json($airportTaxiReference);
            const flightTicketReferences = @json($flightTicketReferences);
            const regionalLodgingReferences = @json($regionalLodgingReferences);
            const nationalLodgingReferences = @json($nationalLodgingReferences);
            const representationReferences = @json($representationReferences);
            const dailyAllowanceReferences = @json($dailyAllowanceReferences);
            const dailyAllowanceRateInput = document.getElementById('daily_allowance_rate');
            const dailyAllowanceState = document.getElementById('daily-allowance-sbu-state');
            const representationRateInput = document.getElementById('representation_rate');
            const representationState = document.getElementById('representation-sbu-state');
            const ticketSbuState = document.getElementById('ticket-sbu-state');
            const lodgingRateInput = document.getElementById('lodging_rate');
            const lodgingReceiptInput = document.getElementById('lodging_has_receipt');
            const lodgingState = document.getElementById('lodging-sbu-state');
            const localTransportSbuState = document.getElementById('local-transport-sbu-state');
            const localTransportFieldWrappers = document.querySelectorAll('[data-local-transport-field-wrapper]');
            const localTransportLabels = document.querySelectorAll('[data-local-transport-label]');
            const sbuComparisonState = document.getElementById('sbu-comparison-state');
            const sbuComparisonTotal = document.getElementById('sbu-comparison-total');
            const sbuComparisonRows = document.getElementById('sbu-comparison-rows');
            
            const activityFileInput = document.getElementById('activity_file');
            const receiptFileInput = document.getElementById('receipt_file');
            const reportFileInput = document.getElementById('report_file');
            const removeActivityInput = document.getElementById('remove_activity_file');
            const removeReceiptInput = document.getElementById('remove_receipt_file');
            const removeReportInput = document.getElementById('remove_report_file');
            const activityCurrentWrapper = document.getElementById('activity-current-wrapper');
            const receiptCurrentWrapper = document.getElementById('receipt-current-wrapper');
            const reportCurrentWrapper = document.getElementById('report-current-wrapper');

            const output = (key) => document.querySelector(`[data-total-output="${key}"]`);

            const labelMap = {
                Pesawat: 'Maskapai',
                Kapal: 'Nama Kapal',
                Speed: 'Nama Speed',
                Kereta: 'Nama Kereta',
                Bus: 'Nama Bus',
                Lainnya: 'Operator Transport',
            };

            const districtAliasMap = {
                'Kota Ternate': {
                    'Ternate Tengah': ['Kecamatan-kecamatan dalam Pulau Ternate'],
                    'Ternate Utara': ['Kecamatan-kecamatan dalam Pulau Ternate'],
                    'Ternate Selatan': ['Kecamatan-kecamatan dalam Pulau Ternate'],
                    'Pulau Ternate': ['Kecamatan-kecamatan dalam Pulau Ternate'],
                    'Pulau Hiri': ['Kecamatan Hiri', 'Hiri'],
                    'Moti': ['Kecamatan Moti'],
                    'Batang Dua': ['Kecamatan Batang Dua'],
                },
                'Kota Tidore Kepulauan': {
                    'Tidore': ['Kecamatan Tidore'],
                    'Tidore Selatan': ['Kecamatan Tidore Selatan'],
                    'Tidore Utara': ['Kecamatan Tidore Utara'],
                    'Tidore Timur': ['Kecamatan Tidore Timur'],
                    'Oba': ['Kecamatan Oba'],
                    'Oba Utara': ['Kecamatan Oba Utara'],
                    'Oba Tengah': ['Kecamatan Oba Tengah'],
                    'Oba Selatan': ['Kecamatan Oba Selatan'],
                },
            };

            const provinceAliasMap = {
                'dki jakarta': 'DKI JAKARTA',
                'yogyakarta': 'YOGYARTA',
                'di yogyakarta': 'YOGYARTA',
                'kepulauan riau': 'KEPULAUN RIAU',
                'sulawesi tenggara': 'SULAWESI TENGGARA',
            };

            const isRegionalTrip = () => (categoryInput?.value || '') === 'Perjadin Dalam Daerah';
            const isWithinSofifiTrip = () => isRegionalTrip() && (regionalTripScopeInput?.value || '') === 'dalam_kota_sofifi';
            const isOutsideSofifiTrip = () => isRegionalTrip() && (regionalTripScopeInput?.value || '') === 'luar_kota_sofifi';
            const isEnabled = (name) => !!document.querySelector(`input[name="${name}"][type="checkbox"]`)?.checked;
            const selectedOutsideDestination = () => outsideDestinationOptions.find((option) => option.value === (destinationCitySelect?.value || destinationCityInput?.value || '')) || null;

            const syncDestinationCityValue = () => {
                if (!destinationCityInput) {
                    return;
                }

                if (isRegionalTrip()) {
                    destinationCityInput.value = destinationCityManualInput?.value || '';
                    return;
                }

                destinationCityInput.value = destinationCitySelect?.value || '';
                if (destinationCityManualInput) {
                    const selectedOption = destinationCitySelect?.selectedOptions?.[0];
                    destinationCityManualInput.value = selectedOption && selectedOption.value ? selectedOption.textContent : '';
                }
            };

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

            const hasMeaningfulValue = (value) => {
                if (value === null || value === undefined) {
                    return false;
                }

                if (typeof value === 'number') {
                    return value > 0;
                }

                const text = value.toString().trim();
                if (!text) {
                    return false;
                }

                const digits = digitsOnly(text);
                if (digits) {
                    return Number(digits) > 0;
                }

                return true;
            };

            const ensureToggleStateFromFields = () => {
                const toggleConfigs = [
                    {
                        name: 'daily_allowance_enabled',
                        fields: [
                            document.querySelector('[data-multiply-left="daily"]'),
                            document.querySelector('[data-multiply-right="daily"]'),
                        ],
                    },
                    {
                        name: 'representation_enabled',
                        fields: [
                            document.querySelector('[data-multiply-left="representation"]'),
                            document.querySelector('[data-multiply-right="representation"]'),
                        ],
                    },
                    {
                        name: 'ticket_enabled',
                        fields: [
                            transportTypeInput,
                            document.getElementById('ticket_departure_date'),
                            document.getElementById('ticket_return_date'),
                            ticketDeparture,
                            ticketReturn,
                            document.getElementById('ticket_departure_operator'),
                            document.getElementById('ticket_return_operator'),
                        ],
                    },
                    {
                        name: 'lodging_enabled',
                        fields: [
                            document.querySelector('[data-multiply-left="lodging"]'),
                            document.querySelector('[data-multiply-right="lodging"]'),
                            document.getElementById('lodging_hotel_name'),
                        ],
                    },
                    {
                        name: 'local_transport_enabled',
                        fields: Array.from(localTransportInputs),
                    },
                    {
                        name: 'other_cost_enabled',
                        fields: [otherCostInput],
                    },
                ];

                toggleConfigs.forEach(({ name, fields }) => {
                    const checkbox = document.querySelector(`input[name="${name}"][type="checkbox"]`);
                    if (!checkbox) {
                        return;
                    }

                    const shouldEnable = fields.some((field) => hasMeaningfulValue(field?.value));
                    if (shouldEnable) {
                        checkbox.checked = true;
                    }
                });
            };

            const populateDistrictSelect = (select, regency, selectedValue = '') => {
                if (!select) {
                    return;
                }

                const placeholder = select.dataset.districtTarget === 'origin'
                    ? 'Pilih kecamatan asal'
                    : 'Pilih kecamatan tujuan';
                const options = select.dataset.districtTarget === 'origin'
                    ? (originDistrictOptions[regency] || [])
                    : (destinationDistrictOptions[regency] || []);

                select.innerHTML = '';
                const defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = placeholder;
                select.appendChild(defaultOption);

                options.forEach((district) => {
                    const option = document.createElement('option');
                    option.value = district;
                    option.textContent = district;
                    if (district === selectedValue) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            };

            const updateRouteFieldsVisibility = () => {
                const showRegional = isRegionalTrip();
                regionalRouteFields?.classList.toggle('hidden', !showRegional);
                localTransportReferencePanel?.classList.toggle('hidden', !showRegional);
                destinationCityManualWrapper?.classList.toggle('hidden', !showRegional);
                destinationCitySbuWrapper?.classList.toggle('hidden', showRegional);
                outsideRegionSbuSummary?.classList.toggle('hidden', showRegional);
                if (destinationCityInput) {
                    destinationCityInput.readOnly = !showRegional;
                }
                syncDestinationCityValue();
            };

            const updateRegionalTripScopeVisibility = () => {
                const showSofifiDuration = isRegionalTrip() && (regionalTripScopeInput?.value || '') === 'dalam_kota_sofifi';
                sofifiDurationWrapper?.classList.toggle('hidden', !showSofifiDuration);

                if (!showSofifiDuration && sofifiOver8HoursInput) {
                    sofifiOver8HoursInput.checked = false;
                }
            };

            const districtKeywords = (regency, district) => {
                if (!district) {
                    return [];
                }

                const aliases = districtAliasMap[regency]?.[district] || [];
                return [...new Set([district, stripDistrictPrefix(district), ...aliases, ...aliases.map((alias) => stripDistrictPrefix(alias))])].filter(Boolean);
            };

            const localTransportRowsForRegency = (regency) => localTransportReferences.filter((row) =>
                sameNormalized(row.destination_regency, regency)
                || sameNormalized(row.origin_regency, regency)
                || sameNormalized(row.area_name, regency)
            );

            const stripDistrictPrefix = (value) => normalizeText(value).replace(/^kecamatan\s+/, '').trim();
            const routeOptionLabel = (row) => `${row.origin_label} -> ${row.destination_label} (${row.amount_label})`;

            const localTransportRoutePool = () => {
                const destinationRegency = destinationRegencyInput?.value || '';
                const originDistrict = originDistrictInput?.value || '';
                const destinationDistrict = destinationDistrictInput?.value || '';
                const rows = destinationRegency ? localTransportRowsForRegency(destinationRegency) : [];

                return rows
                    .filter((row) => Number(row.amount || 0) > 0)
                    .sort((left, right) => {
                        const startMatchLeft = sameNormalized(left.origin_label, originDistrict) ? 0 : 1;
                        const startMatchRight = sameNormalized(right.origin_label, originDistrict) ? 0 : 1;
                        if (startMatchLeft !== startMatchRight) {
                            return startMatchLeft - startMatchRight;
                        }

                        const endMatchLeft = sameNormalized(left.destination_label, destinationDistrict) ? 0 : 1;
                        const endMatchRight = sameNormalized(right.destination_label, destinationDistrict) ? 0 : 1;
                        if (endMatchLeft !== endMatchRight) {
                            return endMatchLeft - endMatchRight;
                        }

                        return (left.sort_order || 0) - (right.sort_order || 0);
                    });
            };

            const populateLocalTransportSegmentSelect = (select, selectedId = '') => {
                if (!select) {
                    return;
                }

                const routePool = localTransportRoutePool();
                select.innerHTML = '';

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = routePool.length
                    ? 'Pilih rute / segment dari master SBU'
                    : 'Belum ada rute SBU untuk kabupaten tujuan ini';
                select.appendChild(placeholder);

                routePool.forEach((row) => {
                    const option = document.createElement('option');
                    option.value = String(row.id);
                    option.textContent = routeOptionLabel(row);
                    if (String(row.id) === String(selectedId)) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            };

            const syncLocalTransportSegmentSummary = () => {
                if (!localTransportSegmentList || !localTransportReferenceHighlight || !localTransportReferenceState || !localTransportSegmentEmpty) {
                    return;
                }

                const showRegional = isRegionalTrip();
                const destinationRegency = destinationRegencyInput?.value || '';
                const destinationDistrict = destinationDistrictInput?.value || '';
                const originDistrict = originDistrictInput?.value || '';
                const routePool = localTransportRoutePool();
                const segmentRows = Array.from(localTransportSegmentList.querySelectorAll('[data-local-transport-segment-row]'));
                const selectedDetails = segmentRows
                    .map((row) => row.dataset.selectedRouteId || '')
                    .filter(Boolean)
                    .map((id) => localTransportReferences.find((entry) => String(entry.id) === String(id)))
                    .filter(Boolean)
                    .map((entry) => ({
                        from: entry.origin_label,
                        to: entry.destination_label,
                        amount: Number(entry.amount || 0),
                        amountLabel: entry.amount_label,
                    }));

                localTransportSegmentEmpty.classList.toggle('hidden', segmentRows.length > 0);

                if (!showRegional) {
                    localTransportReferenceHighlight.classList.add('hidden');
                    localTransportReferenceState.textContent = 'Acuan SBU hanya tampil untuk perjadin dalam daerah.';
                    return;
                }

                if (!destinationRegency || !destinationDistrict) {
                    localTransportReferenceHighlight.classList.add('hidden');
                    localTransportReferenceState.textContent = 'Pilih kabupaten dan kecamatan tujuan, lalu tambahkan segment rute dari master SBU transport lokal.';
                    return;
                }

                if (!routePool.length) {
                    localTransportReferenceHighlight.classList.add('hidden');
                    localTransportReferenceState.textContent = `Belum ada data segment SBU transport lokal yang tersedia untuk ${destinationRegency}.`;
                    return;
                }

                if (!selectedDetails.length) {
                    localTransportReferenceHighlight.classList.add('hidden');
                    localTransportReferenceState.textContent = `Belum ada segment dipilih untuk tujuan ${destinationDistrict}, ${destinationRegency}. Tambahkan rute sesuai tahapan perjalanan.`;
                    return;
                }

                const total = selectedDetails.reduce((carry, detail) => carry + detail.amount, 0);
                const detailRows = selectedDetails
                    .map((detail) => `<span class="block">- ${escapeHtml(detail.from)} -> ${escapeHtml(detail.to)} : ${escapeHtml(detail.amountLabel)}</span>`)
                    .join('');

                localTransportReferenceState.textContent = 'Nominal maksimal berikut berasal dari penjumlahan seluruh segment SBU yang dipilih operator.';
                localTransportReferenceHighlight.innerHTML = `
                    <div class="space-y-3 text-sm leading-6 text-slate-700">
                        <p>Nominal Maksimal SBU Transport Lokal dari <span class="font-semibold text-slate-900">${escapeHtml(originDistrict || '-')}</span> ke <span class="font-semibold text-slate-900">${escapeHtml(destinationDistrict)}</span> adalah <span class="font-semibold text-emerald-700">${escapeHtml(moneyLabel(total))}</span></p>
                        <div>
                            <p class="font-semibold text-slate-900">Rincian Perjalanan:</p>
                            <div class="mt-1 space-y-1">${detailRows}</div>
                        </div>
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.18em] text-emerald-700">Total SBU Transport Lokal</p>
                            <p class="mt-1 text-base font-semibold text-emerald-700">${escapeHtml(moneyLabel(total))}</p>
                        </div>
                    </div>`;
                localTransportReferenceHighlight.classList.remove('hidden');
            };

            const createLocalTransportSegmentRow = (selectedId = '') => {
                if (!localTransportSegmentList) {
                    return;
                }

                const wrapper = document.createElement('div');
                wrapper.dataset.localTransportSegmentRow = '1';
                wrapper.dataset.selectedRouteId = '';
                wrapper.className = 'rounded-2xl border border-slate-200 bg-white p-4 shadow-sm';

                wrapper.innerHTML = `
                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px_auto] lg:items-end">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Rute / Segment</label>
                            <select class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" data-local-transport-segment-select></select>
                            <input type="hidden" name="local_transport_segment_ids[]" value="" data-local-transport-segment-hidden />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Nominal SBU</label>
                            <input type="text" readonly value="" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-900 outline-none" data-local-transport-segment-amount />
                        </div>
                        <button type="button" class="inline-flex items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 transition hover:bg-rose-100" data-local-transport-segment-remove>
                            Hapus
                        </button>
                    </div>
                `;

                const select = wrapper.querySelector('[data-local-transport-segment-select]');
                const amountOutput = wrapper.querySelector('[data-local-transport-segment-amount]');
                const removeButton = wrapper.querySelector('[data-local-transport-segment-remove]');
                const hiddenInput = wrapper.querySelector('[data-local-transport-segment-hidden]');

                const syncRow = () => {
                    const selectedEntry = localTransportReferences.find((entry) => String(entry.id) === String(select.value)) || null;
                    wrapper.dataset.selectedRouteId = selectedEntry ? String(selectedEntry.id) : '';
                    if (hiddenInput) {
                        hiddenInput.value = selectedEntry ? String(selectedEntry.id) : '';
                    }
                    amountOutput.value = selectedEntry ? selectedEntry.amount_label : '';
                    syncLocalTransportSegmentSummary();
                    recalculateTotalsOnly();
                };

                populateLocalTransportSegmentSelect(select, selectedId);
                select.addEventListener('change', syncRow);
                removeButton.addEventListener('click', () => {
                    wrapper.remove();
                    syncLocalTransportSegmentSummary();
                    recalculateTotalsOnly();
                });

                localTransportSegmentList.appendChild(wrapper);
                syncRow();
            };

            const refreshLocalTransportSegmentOptions = () => {
                if (!localTransportSegmentList) {
                    return;
                }

                localTransportSegmentList.querySelectorAll('[data-local-transport-segment-row]').forEach((row) => {
                    const select = row.querySelector('[data-local-transport-segment-select]');
                    const amountOutput = row.querySelector('[data-local-transport-segment-amount]');
                    const selectedId = row.dataset.selectedRouteId || '';
                    populateLocalTransportSegmentSelect(select, selectedId);
                    const selectedEntry = localTransportReferences.find((entry) => String(entry.id) === String(select.value)) || null;
                    row.dataset.selectedRouteId = selectedEntry ? String(selectedEntry.id) : '';
                    if (amountOutput) {
                        amountOutput.value = selectedEntry ? selectedEntry.amount_label : '';
                    }
                });
                syncLocalTransportSegmentSummary();
            };

            const updateLocalTransportReferences = () => {
                if (!localTransportReferencePanel || !localTransportReferenceState || !localTransportReferenceHighlight || !localTransportSegmentList || !localTransportSegmentEmpty) {
                    return;
                }

                if (!isRegionalTrip()) {
                    localTransportSegmentList.innerHTML = '';
                    localTransportSegmentEmpty.classList.remove('hidden');
                    localTransportReferenceHighlight.classList.add('hidden');
                    localTransportReferenceState.textContent = 'Acuan SBU hanya tampil untuk perjadin dalam daerah.';
                    if (addLocalTransportSegmentButton) {
                        addLocalTransportSegmentButton.disabled = true;
                    }
                    return;
                }

                const routePool = localTransportRoutePool();
                if (addLocalTransportSegmentButton) {
                    addLocalTransportSegmentButton.disabled = routePool.length === 0;
                }
                refreshLocalTransportSegmentOptions();
            };

            const selectedLocalTransportReference = () => {
                if (!isRegionalTrip() || !localTransportSegmentList) {
                    return null;
                }

                const details = Array.from(localTransportSegmentList.querySelectorAll('[data-local-transport-segment-row]'))
                    .map((row) => row.dataset.selectedRouteId || '')
                    .filter(Boolean)
                    .map((id) => localTransportReferences.find((entry) => String(entry.id) === String(id)))
                    .filter(Boolean)
                    .map((entry) => ({
                        from: entry.origin_label,
                        to: entry.destination_label,
                        amount: Number(entry.amount || 0),
                        amountLabel: entry.amount_label,
                    }));

                if (!details.length) {
                    return null;
                }

                const total = details.reduce((carry, detail) => carry + detail.amount, 0);

                return {
                    details,
                    total,
                    totalLabel: moneyLabel(total),
                    startLabel: details[0]?.from || '',
                    endLabel: details.at(-1)?.to || '',
                };
            };
            const selectedOutsideProvinceName = () => {
                const destination = selectedOutsideDestination();

                if (!destination) {
                    return '';
                }

                if (destination.province_name) {
                    return destination.province_name;
                }

                const fallbackName = destination.ticket_destination || destination.value || '';
                const dailyAllowanceProvince = findProvinceReference(dailyAllowanceReferences, 'province_name', fallbackName);
                const lodgingProvince = findProvinceReference(nationalLodgingReferences, 'province_name', fallbackName);

                return dailyAllowanceProvince?.province_name || lodgingProvince?.province_name || '';
            };
            const findOutsideProvinceReference = (collection, property) => {
                const provinceName = selectedOutsideProvinceName();
                if (!provinceName) {
                    return null;
                }

                return collection.find((row) => normalizeText(row[property]) === normalizeText(provinceName))
                    || findProvinceReference(collection, property, provinceName);
            };

            const dailyAllowanceReferenceEntry = () => isRegionalTrip()
                ? dailyAllowanceReferences.find((entry) => normalizeText(entry.province_name) === normalizeText('Maluku Utara'))
                : findOutsideProvinceReference(dailyAllowanceReferences, 'province_name');

            const dailyAllowanceApplicableRate = (row) => {
                if (!row) {
                    return 0;
                }

                if (isRegionalTrip()) {
                    if (isOutsideSofifiTrip()) {
                        return Number(row.outside_city_amount || 0);
                    }

                    if (isWithinSofifiTrip()) {
                        return sofifiOver8HoursInput?.checked
                            ? Number(row.sofifi_inside_city_over_8_hours_amount || 0)
                            : 0;
                    }

                    return 0;
                }

                return Number(row.outside_city_amount || 0);
            };

            const representationReferenceEntry = () => {
                const echelon = (echelonInput?.value || '').trim();
                let key = null;

                if (echelon === '1') {
                    key = 'PEJABAT ESELON I';
                } else if (echelon === '2') {
                    key = 'PEJABAT ESELON II';
                }

                return key
                    ? representationReferences.find((entry) => normalizeText(entry.position_group) === normalizeText(key))
                    : null;
            };

            const representationApplicableRate = (row) => {
                if (!row) {
                    return 0;
                }

                if (isRegionalTrip()) {
                    if (isOutsideSofifiTrip()) {
                        return Number(row.outside_city_amount || 0);
                    }

                    if (isWithinSofifiTrip()) {
                        return sofifiOver8HoursInput?.checked
                            ? Number(row.inside_city_over_8_hours_amount || 0)
                            : 0;
                    }

                    return 0;
                }

                return Number(row.outside_city_amount || 0);
            };

            const ticketReferenceEntry = () => {
                const destination = selectedOutsideDestination();
                if (!destination) {
                    return null;
                }

                return flightTicketReferences.find((entry) => normalizeText(entry.origin_city) === 'ternate' && normalizeText(entry.destination_city) === normalizeText(destination.ticket_destination));
            };

            const gradeTier = () => {
                const grade = (gradeInput?.value || '').toUpperCase().trim();
                if (!grade) {
                    return null;
                }

                if (grade.startsWith('4')) {
                    return 'gol_4';
                }

                if (['3', '2', '1'].includes(grade.charAt(0))) {
                    return 'gol_3_2_1';
                }

                return null;
            };

            const profileSelection = () => ({
                echelon: (echelonInput?.value || '').trim(),
                grade: (gradeInput?.value || '').toUpperCase().trim(),
                gradeTier: gradeTier(),
            });

            const lodgingAmountByProfile = (entry) => {
                if (!entry) {
                    return { amount: 0, rule: null };
                }

                const profile = profileSelection();

                if (profile.echelon === '1') {
                    return {
                        amount: Number(entry.head_region_amount || 0),
                        rule: 'Eselon 1',
                    };
                }

                if (profile.echelon === '2') {
                    return {
                        amount: Number(entry.member_eselon_2_amount || 0),
                        rule: 'Eselon 2',
                    };
                }

                if (profile.echelon === '3' && profile.gradeTier === 'gol_4') {
                    return {
                        amount: Number(entry.eselon_3_gol_4_amount || 0),
                        rule: `Eselon 3 / Golongan ${profile.grade || '-'}`,
                    };
                }

                if (profile.echelon === '4' && profile.gradeTier === 'gol_3_2_1') {
                    return {
                        amount: Number(entry.eselon_4_gol_3_2_1_amount || 0),
                        rule: `Eselon 4 / Golongan ${profile.grade || '-'}`,
                    };
                }

                return { amount: 0, rule: null };
            };

            const lodgingComparisonByEchelon = (entry) => {
                if (!entry) {
                    return { amount: 0, rule: null };
                }

                const echelon = (echelonInput?.value || '').trim();

                if (echelon === '1') {
                    return {
                        amount: Number(entry.head_region_amount || 0),
                        rule: 'Eselon 1',
                    };
                }

                if (echelon === '2') {
                    return {
                        amount: Number(entry.member_eselon_2_amount || 0),
                        rule: 'Eselon 2',
                    };
                }

                if (echelon === '3') {
                    return {
                        amount: Number(entry.eselon_3_gol_4_amount || 0),
                        rule: 'Eselon 3',
                    };
                }

                if (echelon === '4') {
                    return {
                        amount: Number(entry.eselon_4_gol_3_2_1_amount || 0),
                        rule: 'Eselon 4',
                    };
                }

                return { amount: 0, rule: null };
            };

            const normalizedProvinceTokens = (value) => {
                const text = normalizeText(value);
                if (!text) {
                    return [];
                }

                const aliases = [text];
                if (provinceAliasMap[text]) {
                    aliases.push(normalizeText(provinceAliasMap[text]));
                }
                return [...new Set(aliases)];
            };

            const findProvinceReference = (collection, property, rawValue) => {
                const tokens = normalizedProvinceTokens(rawValue);
                if (!tokens.length) {
                    return null;
                }

                return collection.find((row) => tokens.includes(normalizeText(row[property])))
                    || collection.find((row) => {
                        const rowText = normalizeText(row[property]);
                        return tokens.some((token) => rowText.includes(token) || token.includes(rowText));
                    })
                    || null;
            };

            const findRegionalLodgingReference = () => {
                const regency = destinationRegencyInput?.value || '';
                if (!regency) {
                    return null;
                }

                return regionalLodgingReferences.find((entry) => normalizeText(entry.region_name) === normalizeText(regency))
                    || regionalLodgingReferences.find((entry) => {
                        const rowText = normalizeText(entry.region_name);
                        const target = normalizeText(regency);
                        return rowText.includes(target) || target.includes(rowText);
                    })
                    || null;
            };

            const regionalLodgingReferenceName = () => destinationRegencyInput?.value || '';

            const lodgingReferenceEntry = () => isRegionalTrip()
                ? findRegionalLodgingReference()
                : findOutsideProvinceReference(nationalLodgingReferences, 'province_name');

            const lodgingAppliedRate = (baseRate) => {
                const normalizedBaseRate = Number(baseRate || 0);
                if (normalizedBaseRate <= 0) {
                    return 0;
                }

                return lodgingReceiptInput?.checked ? normalizedBaseRate : Math.round(normalizedBaseRate * 0.3);
            };

            const updateLodgingRateFromReference = () => {
                if (!lodgingRateInput) {
                    return;
                }

                lodgingRateInput.readOnly = false;
                const row = lodgingReferenceEntry();
                const lodgingProfile = lodgingComparisonByEchelon(row);
                const baseRate = lodgingProfile.amount;
                const destination = selectedOutsideDestination();
                const provinceName = selectedOutsideProvinceName();
                if (baseRate > 0) {
                    const appliedRate = lodgingAppliedRate(baseRate);
                    if (lodgingState) {
                        const regionalTarget = regionalLodgingReferenceName();
                        lodgingState.textContent = isRegionalTrip()
                            ? (lodgingReceiptInput?.checked
                                ? `Batas maksimal sesuai SBU penginapan ${regionalTarget ? `di ${regionalTarget}` : 'dalam daerah'} adalah ${moneyLabel(baseRate)} per malam berdasarkan ${lodgingProfile.rule || 'profil jabatan'}. Nilai penginapan tetap diinput manual.`
                                : `Batas maksimal tanpa nota ${regionalTarget ? `di ${regionalTarget}` : 'dalam daerah'} adalah lumpsum 30% dari SBU, yaitu ${moneyLabel(appliedRate)} per malam dari acuan ${moneyLabel(baseRate)} berdasarkan ${lodgingProfile.rule || 'profil jabatan'}. Total dibayar di form dihitung 30% dari nominal input manual.`)
                            : (lodgingReceiptInput?.checked
                                ? `Batas maksimal sesuai SBU penginapan adalah ${moneyLabel(baseRate)} per malam berdasarkan ${lodgingProfile.rule || 'profil jabatan'}. Nilai penginapan tetap diinput manual.`
                                : `Batas maksimal tanpa nota adalah lumpsum 30% dari SBU, yaitu ${moneyLabel(appliedRate)} per malam dari acuan ${moneyLabel(baseRate)} berdasarkan ${lodgingProfile.rule || 'profil jabatan'}. Total dibayar di form dihitung 30% dari nominal input manual.`);
                    }
                } else {
                    if (lodgingState) {
                        lodgingState.textContent = isRegionalTrip()
                            ? 'Pilih kabupaten tujuan pada poin 00 agar acuan SBU penginapan dalam daerah bisa dimuat sesuai eselon. Nilai penginapan tetap diinput manual.'
                            : !destination
                                ? 'Pilih tujuan luar daerah untuk melihat acuan penginapan. Nilai penginapan tetap diinput manual.'
                                : !provinceName
                                    ? 'Provinsi untuk kota tujuan belum tersedia.'
                                    : 'Data SBU penginapan provinsi belum tersedia atau belum cocok dengan eselon yang dipilih. Nilai penginapan tetap diinput manual.';
                    }
                }

                  queueTotalsRefresh();
            };

            const updateDailyAllowanceRateFromReference = () => {
                if (!dailyAllowanceRateInput) {
                    return;
                }

                if (isRegionalTrip()) {
                    dailyAllowanceRateInput.readOnly = true;
                    const row = dailyAllowanceReferenceEntry();
                    const rate = dailyAllowanceApplicableRate(row);
                    dailyAllowanceRateInput.value = rate > 0 ? formatNominal(rate) : '0';
                    if (dailyAllowanceState) {
                        if (!regionalTripScopeInput?.value) {
                            dailyAllowanceState.textContent = 'Pilih jenis perjalanan Sofifi untuk memuat acuan uang harian Maluku Utara.';
                        } else if (isWithinSofifiTrip()) {
                            dailyAllowanceState.textContent = sofifiOver8HoursInput?.checked
                                ? `Acuan Maluku Utara untuk Dalam Kota Sofifi lebih dari 8 jam adalah ${moneyLabel(rate)} per hari.`
                                : 'Dalam Kota Sofifi tanpa checklist lebih dari 8 jam tidak mendapat uang harian, sehingga nominal 0.';
                        } else {
                            dailyAllowanceState.textContent = rate > 0
                                ? `Acuan Maluku Utara untuk Luar Kota Sofifi adalah ${moneyLabel(rate)} per hari.`
                                : 'Data SBU uang harian Maluku Utara belum tersedia.';
                        }
                    }
                    queueTotalsRefresh();
                    return;
                }

                dailyAllowanceRateInput.readOnly = true;
                const destination = selectedOutsideDestination();
                const provinceName = selectedOutsideProvinceName();
                const row = dailyAllowanceReferenceEntry();
                const rate = row ? Number(row.outside_city_amount || 0) : 0;
                dailyAllowanceRateInput.value = rate > 0 ? formatNominal(rate) : '';
                  if (dailyAllowanceState) {
                      dailyAllowanceState.textContent = !destination
                          ? 'Pilih tujuan luar daerah untuk memuat acuan uang harian.'
                          : !provinceName
                              ? 'Provinsi untuk kota tujuan belum tersedia.'
                              : rate > 0
                                  ? `Provinsi referensi uang harian: ${provinceName}. Uang Harian ${moneyLabel(rate)} per hari. Total akan dihitung otomatis dari jumlah hari.`
                                  : 'Data SBU uang harian provinsi belum tersedia.';
                  }

                  queueTotalsRefresh();
              };

            const updateRepresentationRateFromReference = () => {
                if (!representationRateInput) {
                    return;
                }

                if (isRegionalTrip()) {
                    representationRateInput.readOnly = true;
                    const row = representationReferenceEntry();
                    const rate = representationApplicableRate(row);
                    representationRateInput.value = rate > 0 ? formatNominal(rate) : '0';
                    if (representationState) {
                        if (!regionalTripScopeInput?.value) {
                            representationState.textContent = 'Pilih jenis perjalanan Sofifi untuk memuat acuan representasi.';
                        } else if (!['1', '2'].includes((echelonInput?.value || '').trim())) {
                            representationState.textContent = `Eselon ${echelonInput?.value || '-'} tidak mendapat representasi sesuai acuan SBU.`;
                        } else if (isWithinSofifiTrip()) {
                            representationState.textContent = sofifiOver8HoursInput?.checked
                                ? `Acuan representasi untuk Dalam Kota Sofifi lebih dari 8 jam adalah ${moneyLabel(rate)} per hari.`
                                : 'Dalam Kota Sofifi tanpa checklist lebih dari 8 jam tidak mendapat representasi, sehingga nominal 0.';
                        } else {
                            representationState.textContent = rate > 0
                                ? `Acuan representasi untuk Luar Kota Sofifi adalah ${moneyLabel(rate)} per hari.`
                                : 'Data SBU representasi belum tersedia untuk eselon yang dipilih.';
                        }
                    }
                    queueTotalsRefresh();
                    return;
                }

                representationRateInput.readOnly = true;
                const row = representationReferenceEntry();
                const rate = representationApplicableRate(row);
                representationRateInput.value = rate > 0 ? formatNominal(rate) : '0';
                if (representationState) {
                    representationState.textContent = rate > 0
                        ? `Representasi: ${moneyLabel(rate)} per hari berdasarkan Eselon ${echelonInput?.value || '-'}. Total akan dihitung otomatis dari jumlah hari.`
                        : `Eselon ${echelonInput?.value || '-'} tidak mendapat representasi sesuai acuan SBU.`;
                }

                  queueTotalsRefresh();
              };

            const updateTicketSbuReference = () => {
                if (!ticketSbuState) {
                    return;
                }

                if (isRegionalTrip()) {
                    ticketSbuState.textContent = 'Tiket untuk perjadin dalam daerah masih mengikuti input manual tanpa acuan otomatis.';
                    return;
                }

                const row = ticketReferenceEntry();
                const rate = row ? Number(row.economy_amount || row.business_amount || 0) : 0;
                ticketSbuState.textContent = rate > 0
                    ? `Batas maksimal sesuai SBU tiket pesawat tujuan ${row.destination_city}: ${moneyLabel(rate)}. Nilai tiket tetap diinput manual.`
                    : 'Data SBU tiket pesawat belum tersedia.';
            };

            const updateLocalTransportSbuReference = () => {
                if (!localTransportSbuState) {
                    return;
                }

                localTransportFieldWrappers.forEach((wrapper) => {
                    const field = wrapper.dataset.localTransportField || '';
                    const input = wrapper.querySelector('input');
                    wrapper.classList.remove('hidden');
                    if (input) {
                        input.readOnly = false;
                    }
                });

                localTransportLabels.forEach((label) => {
                    const field = label.dataset.localTransportLabel;
                    if (field === 'local_transport_domicile_to_airport') {
                        label.textContent = 'Domisili ke Bandara';
                    } else if (field === 'local_transport_airport_to_domicile') {
                        label.textContent = 'Bandara ke Domisili';
                    } else if (field === 'local_transport_airport_to_hotel') {
                        label.textContent = 'Bandara ke Hotel';
                    } else if (field === 'local_transport_hotel_to_airport') {
                        label.textContent = 'Hotel ke Bandara';
                    } else if (field === 'local_transport_other') {
                        label.textContent = 'Lain-lain';
                    }
                });

                if (isRegionalTrip()) {
                    localTransportSbuState.textContent = 'Transport lokal dalam daerah masih mengikuti rincian rute dan input komponen yang dipakai.';
                    return;
                }

                const rate = Number(airportTaxiReference?.amount || 0);
                localTransportSbuState.textContent = rate > 0
                    ? `Nominal maksimal acuan Taksi Bandara untuk luar daerah adalah ${moneyLabel(rate)}. Nilai ini bisa dibagi operator ke beberapa field transport lokal sesuai kebutuhan, selama total input tetap mengikuti acuan.`
                    : 'Acuan Taksi Bandara belum tersedia di master SBU.';
            };

            const updateOutsideRegionSbuSummary = () => {
                if (!outsideRegionSbuSummaryContent || !outsideRegionSbuSummary) {
                    return;
                }

                if (isRegionalTrip()) {
                    outsideRegionSbuSummary.classList.add('hidden');
                    return;
                }

                outsideRegionSbuSummary.classList.remove('hidden');
                const destination = selectedOutsideDestination();
                const dailyRow = dailyAllowanceReferenceEntry();
                const representationRow = representationReferenceEntry();
                const lodgingRow = lodgingReferenceEntry();
                const ticketRow = ticketReferenceEntry();
                const dailyRate = Number(dailyRow?.outside_city_amount || 0);
                const representationRate = Number(representationRow?.outside_city_amount || 0);
                const lodgingProfile = lodgingAmountByProfile(lodgingRow);
                const lodgingRate = lodgingAppliedRate(lodgingProfile.amount);
                const taxiRate = Number(airportTaxiReference?.amount || 0);
                const ticketRate = Number(ticketRow?.economy_amount || ticketRow?.business_amount || 0);

                outsideRegionSbuSummaryContent.innerHTML = destination
                    ? `<div class="space-y-2">
                            <p><span class="font-semibold text-slate-900">Tujuan:</span> ${escapeHtml(destination.label)}</p>
                            <p><span class="font-semibold text-slate-900">Provinsi Referensi:</span> ${destination.province_name ? escapeHtml(destination.province_name) : 'Belum tersedia'}</p>
                            <p><span class="font-semibold text-slate-900">Uang Harian:</span> ${dailyRate > 0 ? `${escapeHtml(moneyLabel(dailyRate))} per hari` : 'Data SBU belum tersedia'}</p>
                            <p><span class="font-semibold text-slate-900">Representasi:</span> ${representationRate > 0 ? `${escapeHtml(moneyLabel(representationRate))} per hari (Eselon ${escapeHtml(echelonInput?.value || '-')})` : 'Tidak mendapat representasi'}</p>
                            <p><span class="font-semibold text-slate-900">Penginapan:</span> ${lodgingRate > 0 ? `${escapeHtml(moneyLabel(lodgingRate))} per malam${lodgingProfile.rule ? ` (${escapeHtml(lodgingProfile.rule)})` : ''}` : 'Data SBU belum tersedia'}</p>
                            <p><span class="font-semibold text-slate-900">Transport Lokal (Taksi Bandara):</span> ${escapeHtml(moneyLabel(taxiRate))}</p>
                            <p><span class="font-semibold text-slate-900">Maksimal Tiket SBU:</span> ${ticketRate > 0 ? escapeHtml(moneyLabel(ticketRate)) : 'Data SBU belum tersedia'}</p>
                        </div>`
                    : 'Pilih tujuan luar daerah untuk memuat acuan SBU otomatis.';
            };

            const renderSbuComparison = (actuals, grandTotal) => {
                if (!sbuComparisonState || !sbuComparisonTotal || !sbuComparisonRows) {
                    return;
                }

                const items = [];
                const notes = [];

                if (isEnabled('daily_allowance_enabled')) {
                    const row = dailyAllowanceReferenceEntry();
                    const days = Number(document.querySelector('[data-multiply-left="daily"]')?.value || 0);
                    const rate = dailyAllowanceApplicableRate(row);
                    if (rate > 0 && days > 0) {
                        items.push({ label: 'Uang Harian', actual: actuals.daily, maximum: days * rate, detail: `${days} hari x ${moneyLabel(rate)}` });
                    } else {
                        notes.push(isRegionalTrip()
                            ? 'Acuan uang harian dalam daerah mengikuti SBU Maluku Utara dan pilihan jenis perjalanan Sofifi.'
                            : 'Acuan SBU uang harian belum dikenali dari tujuan perjalanan.');
                    }
                }

                if (isEnabled('representation_enabled')) {
                    const row = representationReferenceEntry();
                    const days = Number(document.querySelector('[data-multiply-left="representation"]')?.value || 0);
                    const rate = representationApplicableRate(row);
                    if (rate > 0 && days > 0) {
                        items.push({ label: 'Uang Representasi', actual: actuals.representation, maximum: days * rate, detail: `${days} hari x ${moneyLabel(rate)}` });
                    } else {
                        notes.push(`Eselon ${echelonInput?.value || '-'} tidak mendapat representasi sesuai acuan SBU.`);
                    }
                }

                if (isEnabled('ticket_enabled')) {
                    if (isRegionalTrip()) {
                        notes.push('Acuan tiket pesawat pada panel SBU hanya dipakai untuk perjadin luar daerah.');
                    } else {
                        const row = ticketReferenceEntry();
                        const rate = row ? Number(row.economy_amount || row.business_amount || 0) : 0;
                        if (rate > 0) {
                            items.push({
                                label: 'Tiket Pesawat',
                                actual: actuals.ticket,
                                maximum: rate,
                                detail: `Batas maksimal manual sesuai SBU tujuan ${row.destination_city}`,
                            });
                        } else {
                            notes.push('Acuan SBU tiket pesawat belum dikenali dari kota tujuan.');
                        }
                    }
                }

                if (isEnabled('lodging_enabled')) {
                    const row = lodgingReferenceEntry();
                    const nights = Number(document.querySelector('[data-multiply-left="lodging"]')?.value || 0);
                    const lodgingProfile = lodgingComparisonByEchelon(row);
                    const rate = lodgingAppliedRate(lodgingProfile.amount);
                    if (rate > 0 && nights > 0) {
                        items.push({
                            label: 'Penginapan',
                            actual: actuals.lodging,
                            maximum: nights * rate,
                            detail: lodgingReceiptInput?.checked
                                ? `${nights} malam x ${moneyLabel(rate)} (SBU full)${lodgingProfile.rule ? ` | ${lodgingProfile.rule}` : ''}`
                                : `${nights} malam x ${moneyLabel(rate)} (SBU lumpsum 30%)${lodgingProfile.rule ? ` | ${lodgingProfile.rule}` : ''}`,
                        });
                    } else {
                        notes.push('Acuan SBU penginapan belum dikenali dari tujuan atau eselon yang dipilih.');
                    }
                }

                if (isRegionalTrip()) {
                    const route = selectedLocalTransportReference();
                    const rate = route ? Number(route.total || 0) : 0;
                    if (rate > 0) {
                        items.push({
                            label: 'Transport Lokal',
                            actual: actuals.local_transport,
                            maximum: rate,
                            detail: route.details.map((detail) => `${detail.from} -> ${detail.to}`).join(' | '),
                        });
                    } else if (isEnabled('local_transport_enabled') || actuals.local_transport > 0) {
                        notes.push('Acuan SBU transport lokal belum dikenali dari rute yang dipilih.');
                    }
                } else {
                    const row = airportTaxiReference;
                    const rate = row ? Number(row.amount || 0) : 0;
                    if (rate > 0) {
                        items.push({
                            label: 'Taksi Bandara / Transport Lokal',
                            actual: actuals.local_transport,
                            maximum: rate,
                            detail: `${row.origin_label} ke ${row.destination_label}`,
                        });
                    } else if (isEnabled('local_transport_enabled') || actuals.local_transport > 0) {
                        notes.push('Acuan SBU taksi bandara belum tersedia.');
                    }
                }

                if (isEnabled('other_cost_enabled') && actuals.other > 0) {
                    notes.push('Biaya lain-lain belum memakai acuan SBU khusus, jadi belum masuk total acuan.');
                }

                const recognizedTotal = items.reduce((carry, item) => carry + item.maximum, 0);
                sbuComparisonTotal.textContent = moneyLabel(recognizedTotal);

                if (!items.length && !notes.length) {
                    sbuComparisonState.textContent = 'Aktifkan komponen biaya untuk melihat total acuan SBU.';
                    sbuComparisonState.className = 'mt-1 text-sm text-slate-500';
                    sbuComparisonRows.innerHTML = '';
                    return;
                }

                if (recognizedTotal > 0) {
                    const difference = grandTotal - recognizedTotal;
                    if (difference > 0) {
                        sbuComparisonState.textContent = `Grand total melebihi total acuan SBU sebesar ${moneyLabel(difference)}.`;
                        sbuComparisonState.className = 'mt-1 text-sm text-rose-600';
                    } else if (difference < 0) {
                        sbuComparisonState.textContent = `Grand total masih di bawah total acuan SBU sebesar ${moneyLabel(Math.abs(difference))}.`;
                        sbuComparisonState.className = 'mt-1 text-sm text-emerald-700';
                    } else {
                        sbuComparisonState.textContent = 'Grand total sama persis dengan total acuan SBU yang dikenali.';
                        sbuComparisonState.className = 'mt-1 text-sm text-sky-700';
                    }
                } else {
                    sbuComparisonState.textContent = 'Acuan SBU belum bisa dihitung penuh karena data tujuan atau profil belum cukup lengkap.';
                    sbuComparisonState.className = 'mt-1 text-sm text-amber-600';
                }

                const itemRows = items.map((item) => {
                    const exceeds = item.actual > item.maximum;
                    const tone = exceeds ? 'border-rose-200 bg-rose-50/70 text-rose-700' : 'border-emerald-200 bg-emerald-50/60 text-emerald-700';
                    return `<div class="rounded-2xl border ${tone} px-4 py-3"><div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"><div><p class="text-sm font-semibold">${item.label}</p><p class="mt-1 text-xs opacity-80">${item.detail}</p></div><div class="text-right text-sm font-medium"><p>Input: ${moneyLabel(item.actual)}</p><p class="mt-1">Acuan: ${moneyLabel(item.maximum)}</p></div></div></div>`;
                }).join('');
                const noteRows = notes.map((note) => `<div class="rounded-2xl border border-amber-200 bg-amber-50/80 px-4 py-3 text-sm text-amber-700">${note}</div>`).join('');
                sbuComparisonRows.innerHTML = itemRows + noteRows;
            };

            const calculateTotals = () => {
                ensureToggleStateFromFields();
                updateGroupPanels();

                let grandTotal = 0;
                const actuals = {
                    daily: 0,
                    representation: 0,
                    lodging: 0,
                    ticket: 0,
                    local_transport: 0,
                    other: 0,
                };

                multiplyPairs.forEach(([key, leftSelector, rightSelector, enabledName]) => {
                    const left = Number(document.querySelector(leftSelector)?.value || 0);
                    const right = toNumber(document.querySelector(rightSelector)?.value || 0);
                    const effectiveRight = key === 'lodging' && !lodgingReceiptInput?.checked
                        ? Math.round(right * 0.3)
                        : right;
                    const total = isEnabled(enabledName) ? left * effectiveRight : 0;
                    grandTotal += total;
                    actuals[key] = total;

                    const target = output(key);
                    if (target) {
                        target.value = total > 0 ? `Rp ${formatNominal(total)}` : '';
                    }
                });

                const ticketTotal = isEnabled('ticket_enabled')
                    ? toNumber(ticketDeparture?.value) + toNumber(ticketReturn?.value)
                    : 0;
                grandTotal += ticketTotal;
                actuals.ticket = ticketTotal;
                if (output('ticket')) {
                    output('ticket').value = ticketTotal > 0 ? `Rp ${formatNominal(ticketTotal)}` : '';
                }

                const localTotal = isEnabled('local_transport_enabled')
                    ? Array.from(localTransportInputs).reduce((carry, input) => carry + toNumber(input.value), 0)
                    : 0;
                grandTotal += localTotal;
                actuals.local_transport = localTotal;
                if (output('local_transport')) {
                    output('local_transport').value = localTotal > 0 ? `Rp ${formatNominal(localTotal)}` : '';
                }

                const otherTotal = isEnabled('other_cost_enabled') ? toNumber(otherCostInput?.value) : 0;
                grandTotal += otherTotal;
                actuals.other = otherTotal;

                if (output('grand_total')) {
                    output('grand_total').value = grandTotal > 0 ? `Rp ${formatNominal(grandTotal)}` : 'Rp 0';
                }

                renderSbuComparison(actuals, grandTotal);
            };

            const recalculateTotalsOnly = () => {
                try {
                    calculateTotals();
                } catch (error) {
                    console.error('Gagal menghitung total perjadin:', error);
                }
            };

            let totalsRefreshQueued = false;
            const queueTotalsRefresh = () => {
                if (totalsRefreshQueued) {
                    return;
                }

                totalsRefreshQueued = true;
                window.requestAnimationFrame(() => {
                    totalsRefreshQueued = false;
                    recalculateTotalsOnly();
                });
            };

            const bindDirectRecalculation = (selector, events = ['input', 'change']) => {
                document.querySelectorAll(selector).forEach((field) => {
                    events.forEach((eventName) => {
                        field.addEventListener(eventName, () => {
                            window.requestAnimationFrame(() => {
                                recalculateTotalsOnly();
                            });
                        });
                    });
                });
            };

            const syncCurrentFileState = () => {
                if (activityCurrentWrapper && removeActivityInput && activityFileInput) {
                    activityCurrentWrapper.classList.toggle('hidden', removeActivityInput.checked || Boolean(activityFileInput.files?.length));
                }

                if (receiptCurrentWrapper && removeReceiptInput && receiptFileInput) {
                    receiptCurrentWrapper.classList.toggle('hidden', removeReceiptInput.checked || Boolean(receiptFileInput.files?.length));
                }

                if (reportCurrentWrapper && removeReportInput && reportFileInput) {
                    reportCurrentWrapper.classList.toggle('hidden', removeReportInput.checked || Boolean(reportFileInput.files?.length));
                }
            };

            document.querySelectorAll('input, select').forEach((field) => {
                field.addEventListener('input', () => {
                    syncDestinationCityValue();
                    updateDailyAllowanceRateFromReference();
                    updateRepresentationRateFromReference();
                    updateTicketSbuReference();
                    updateLodgingRateFromReference();
                    updateLocalTransportSbuReference();
                    updateOutsideRegionSbuSummary();
                    recalculateTotalsOnly();
                });
                field.addEventListener('change', () => {
                    syncDestinationCityValue();
                    updateGroupPanels();
                    updateOperatorLabels();
                    updateRouteFieldsVisibility();
                    updateLocalTransportReferences();
                    updateDailyAllowanceRateFromReference();
                    updateRepresentationRateFromReference();
                    updateTicketSbuReference();
                    updateLodgingRateFromReference();
                    updateLocalTransportSbuReference();
                    updateOutsideRegionSbuSummary();
                    recalculateTotalsOnly();
                });
            });

            categoryInput?.addEventListener('change', () => {
                updateRouteFieldsVisibility();
                updateRegionalTripScopeVisibility();
                updateLocalTransportReferences();
                updateDailyAllowanceRateFromReference();
                updateRepresentationRateFromReference();
                updateTicketSbuReference();
                updateLodgingRateFromReference();
                updateLocalTransportSbuReference();
                updateOutsideRegionSbuSummary();
                recalculateTotalsOnly();
            });

            destinationCitySelect?.addEventListener('change', () => {
                syncDestinationCityValue();
                updateDailyAllowanceRateFromReference();
                updateRepresentationRateFromReference();
                updateTicketSbuReference();
                updateLodgingRateFromReference();
                updateLocalTransportSbuReference();
                updateOutsideRegionSbuSummary();
                recalculateTotalsOnly();
            });

            regionalTripScopeInput?.addEventListener('change', () => {
                updateRegionalTripScopeVisibility();
                updateDailyAllowanceRateFromReference();
            });

            sofifiOver8HoursInput?.addEventListener('change', () => {
                updateDailyAllowanceRateFromReference();
            });

            echelonInput?.addEventListener('change', () => {
                updateRepresentationRateFromReference();
                updateLodgingRateFromReference();
                updateOutsideRegionSbuSummary();
                recalculateTotalsOnly();
            });

            gradeInput?.addEventListener('change', () => {
                updateRepresentationRateFromReference();
                updateLodgingRateFromReference();
                updateOutsideRegionSbuSummary();
                recalculateTotalsOnly();
            });

            addLocalTransportSegmentButton?.addEventListener('click', () => {
                createLocalTransportSegmentRow();
                updateLocalTransportReferences();
            });

            bindDirectRecalculation('[data-multiply-left]');
            bindDirectRecalculation('[data-multiply-right]');
            bindDirectRecalculation('[data-sum-ticket]');
            bindDirectRecalculation('[data-local-transport]');
            bindDirectRecalculation('[data-other-cost]');
            bindDirectRecalculation('[data-group-toggle]');
            bindDirectRecalculation('#lodging_has_receipt');

            originRegencyInput?.addEventListener('change', () => {
                populateDistrictSelect(originDistrictInput, originRegencyInput.value, '');
                updateLocalTransportReferences();
            });

            destinationRegencyInput?.addEventListener('change', () => {
                populateDistrictSelect(destinationDistrictInput, destinationRegencyInput.value, '');
                updateLocalTransportReferences();
                updateLodgingRateFromReference();
            });

            originDistrictInput?.addEventListener('change', updateLocalTransportReferences);
            destinationDistrictInput?.addEventListener('change', updateLocalTransportReferences);

            activityFileInput?.addEventListener('change', () => {
                if (removeActivityInput && activityFileInput.files?.length) {
                    removeActivityInput.checked = false;
                }
                syncCurrentFileState();
            });

            receiptFileInput?.addEventListener('change', () => {
                if (removeReceiptInput && receiptFileInput.files?.length) {
                    removeReceiptInput.checked = false;
                }
                syncCurrentFileState();
            });

            reportFileInput?.addEventListener('change', () => {
                if (removeReportInput && reportFileInput.files?.length) {
                    removeReportInput.checked = false;
                }
                syncCurrentFileState();
            });

            removeActivityInput?.addEventListener('change', syncCurrentFileState);
            removeReceiptInput?.addEventListener('change', syncCurrentFileState);
            removeReportInput?.addEventListener('change', syncCurrentFileState);

            updateGroupPanels();
            updateOperatorLabels();
            updateRouteFieldsVisibility();
            updateRegionalTripScopeVisibility();
            syncDestinationCityValue();
            populateDistrictSelect(originDistrictInput, originRegencyInput?.value || '', originDistrictInput?.dataset.selected || originDistrictInput?.value || '');
            populateDistrictSelect(destinationDistrictInput, destinationRegencyInput?.value || '', destinationDistrictInput?.dataset.selected || destinationDistrictInput?.value || '');
            updateLocalTransportReferences();
            if (isRegionalTrip() && initialLocalTransportSegmentIds.length) {
                initialLocalTransportSegmentIds.forEach((id) => {
                    createLocalTransportSegmentRow(String(id));
                });
                updateLocalTransportReferences();
            }
            updateDailyAllowanceRateFromReference();
            updateRepresentationRateFromReference();
            updateTicketSbuReference();
            updateLodgingRateFromReference();
            updateLocalTransportSbuReference();
            updateOutsideRegionSbuSummary();
            recalculateTotalsOnly();
            syncCurrentFileState();

            window.requestAnimationFrame(() => {
                recalculateTotalsOnly();
            });
        });
    </script>
    <x-nominal-input-script />
</x-layout>
