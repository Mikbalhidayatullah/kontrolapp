<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    <div class="space-y-6">
        <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
            <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-amber-600">Form Input Perjadin</p>
                        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Tambah pengajuan perjalanan dinas</h2>
                        <p class="mt-2 text-sm text-slate-500">Semua data perjadin yang diisi di form ini akan langsung tersimpan ke database dan muncul di halaman verifikator.</p>
                    </div>
                    <a href="{{ route('perjadin') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700">
                        Kembali ke Perjadin
                    </a>
                </div>

                <form action="{{ route('add-perjadin.store') }}" method="POST" enctype="multipart/form-data" class="mt-8 space-y-8">
                    @csrf

                    <section class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-100 text-sm font-semibold text-amber-700">01</div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Informasi Utama</h3>
                                <p class="text-sm text-slate-500">Isi data pengajuan dan nama pelaksana perjalanan dinas.</p>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="submission_date" class="block text-sm font-medium text-slate-700">Tanggal Pengajuan</label>
                                <input id="submission_date" name="submission_date" type="date" value="{{ old('submission_date') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            </div>
                            <div>
                                <label for="traveler_name" class="block text-sm font-medium text-slate-700">Nama Pelaksana</label>
                                <input id="traveler_name" name="traveler_name" type="text" value="{{ old('traveler_name') }}" placeholder="Nama petugas / pelaksana" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            </div>
                            <div>
                                <label for="destination_city" class="block text-sm font-medium text-slate-700">Tujuan Kota / Lokasi</label>
                                <input id="destination_city" name="destination_city" type="text" value="{{ old('destination_city') }}" placeholder="Contoh: Ternate" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            </div>
                            <div>
                                <label for="transport_type" class="block text-sm font-medium text-slate-700">Jenis Transportasi</label>
                                <select id="transport_type" name="transport_type" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                    @foreach ($transportTypes as $transportType)
                                        <option value="{{ $transportType }}" @selected(old('transport_type') === $transportType)>{{ $transportType }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-100 text-sm font-semibold text-sky-700">02</div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Jadwal Perjalanan</h3>
                                <p class="text-sm text-slate-500">Tentukan tanggal keberangkatan dan kepulangan.</p>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="departure_date" class="block text-sm font-medium text-slate-700">Tanggal Berangkat</label>
                                <input id="departure_date" name="departure_date" type="date" value="{{ old('departure_date') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            </div>
                            <div>
                                <label for="return_date" class="block text-sm font-medium text-slate-700">Tanggal Kembali</label>
                                <input id="return_date" name="return_date" type="date" value="{{ old('return_date') }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-sm font-semibold text-emerald-700">03</div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Anggaran dan Verifikasi</h3>
                                <p class="text-sm text-slate-500">Masukkan budget pengajuan dan nominal hasil verifikasi bila sudah ada.</p>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                            <div>
                                <label for="budget_amount" class="block text-sm font-medium text-slate-700">Budget Pengajuan</label>
                                <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm transition focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100">
                                    <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                    <input id="budget_amount" name="budget_amount" type="number" min="0" value="{{ old('budget_amount') }}" placeholder="0" class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                </div>
                            </div>
                            <div>
                                <label for="verified_amount" class="block text-sm font-medium text-slate-700">Nominal Terverifikasi</label>
                                <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm transition focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100">
                                    <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                    <input id="verified_amount" name="verified_amount" type="number" min="0" value="{{ old('verified_amount') }}" placeholder="0" class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                </div>
                            </div>
                            <div>
                                <label for="status" class="block text-sm font-medium text-slate-700">Status Verifikasi</label>
                                <select id="status" name="status" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status }}" @selected(old('status') === $status)>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-violet-100 text-sm font-semibold text-violet-700">04</div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Tujuan dan Catatan</h3>
                                <p class="text-sm text-slate-500">Jelaskan maksud perjalanan dan catatan verifikasi jika dibutuhkan.</p>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label for="purpose" class="block text-sm font-medium text-slate-700">Tujuan / Kegiatan</label>
                                <textarea id="purpose" name="purpose" rows="4" placeholder="Contoh: monitoring lapangan, rapat koordinasi, verifikasi dokumen, dan sebagainya" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">{{ old('purpose') }}</textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label for="verifier_notes" class="block text-sm font-medium text-slate-700">Catatan Verifikator</label>
                                <textarea id="verifier_notes" name="verifier_notes" rows="3" placeholder="Isi bila ada revisi, catatan bukti, atau koreksi nominal" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">{{ old('verifier_notes') }}</textarea>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-rose-100 text-sm font-semibold text-rose-700">05</div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Lampiran Bukti</h3>
                                <p class="text-sm text-slate-500">Upload file bukti perjalanan dinas agar bisa diverifikasi lebih mudah.</p>
                            </div>
                        </div>

                        <label for="proof_file" class="flex cursor-pointer flex-col items-center justify-center rounded-[28px] border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center transition hover:border-sky-300 hover:bg-sky-50/40">
                            <span class="rounded-full bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm">Pilih File Bukti Perjadin</span>
                            <span class="mt-4 text-sm text-slate-500">PDF, JPG, atau PNG. Maksimal 5 MB per file.</span>
                            <input id="proof_file" name="proof_file" type="file" class="sr-only" />
                        </label>
                    </section>

                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
                        <a href="{{ route('perjadin') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                            Batal
                        </a>
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700">
                            Simpan Perjadin
                        </button>
                    </div>
                </form>
            </article>

            <div class="space-y-6">
                <article class="rounded-[28px] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-sky-950 p-6 text-white shadow-sm">
                    <p class="text-sm font-medium text-sky-200">Panduan Verifikasi</p>
                    <h3 class="mt-2 text-2xl font-semibold tracking-tight">Alur yang disarankan</h3>
                    <div class="mt-5 space-y-4">
                        <div class="rounded-2xl border border-white/10 bg-white/8 p-4">
                            <p class="font-medium">1. Isi data perjalanan</p>
                            <p class="mt-1 text-sm text-slate-300">Lengkapi nama pelaksana, tujuan, tanggal, dan transportasi.</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/8 p-4">
                            <p class="font-medium">2. Masukkan budget</p>
                            <p class="mt-1 text-sm text-slate-300">Budget awal tetap tersimpan meskipun nominal verifikasi belum final.</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/8 p-4">
                            <p class="font-medium">3. Upload bukti</p>
                            <p class="mt-1 text-sm text-slate-300">Bukti lampiran akan membantu saat pemeriksaan dan penyusunan report perjadin.</p>
                        </div>
                    </div>
                </article>

                <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-emerald-600">Catatan Validasi</p>
                    <div class="mt-4 space-y-3 text-sm text-slate-600">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            Tanggal kembali tidak boleh lebih awal dari tanggal berangkat.
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            Budget pengajuan wajib diisi untuk setiap perjalanan dinas.
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            Nominal terverifikasi boleh dikosongkan dulu jika status masih menunggu verifikasi.
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </div>
</x-layout>
