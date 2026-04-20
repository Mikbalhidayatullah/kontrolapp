<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    @php
        $statusStyles = [
            'LUNAS' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'BAYAR SEBAGIAN' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'HUTANG' => 'bg-rose-50 text-rose-700 ring-rose-200',
            'MASUK' => 'bg-violet-50 text-violet-700 ring-violet-200',
        ];
        $typeLabels = [
            'operasional_langsung' => 'Operasional Dibayar Langsung',
            'operasional_talangan' => 'Operasional Ditalangi',
            'saving_masuk' => 'Saving Masuk',
        ];

        $dominantSource = $entries
            ->groupBy('fund_source')
            ->sortByDesc(fn ($items) => $items->sum(fn ($entry) => $entry->transaction_type === 'saving_masuk' ? $entry->amount_in : $entry->obligation_amount))
            ->keys()
            ->first();

        $dominantLocation = $entries->groupBy('location')->sortByDesc(fn ($items) => $items->count())->keys()->first();
        $dominantType = $entries->groupBy('transaction_type')->sortByDesc(fn ($items) => $items->count())->keys()->first();
    @endphp

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800 shadow-sm">
                {{ session('status') }}
            </div>
        @endif

        <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-medium text-sky-600">Filter Periode</p>
                    <h2 class="mt-1 text-xl font-semibold text-slate-900">Lembar kontrol per bulan</h2>
                    <p class="mt-1 text-sm text-slate-500">Data tabel di bawah ini akan mengikuti bulan dan tahun yang kamu pilih.</p>
                </div>
                <form method="GET" action="{{ route('lembar-kontrol') }}" class="grid gap-3 sm:grid-cols-[minmax(0,220px)_minmax(0,180px)_auto]">
                    <select name="month" class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                        @foreach ($monthOptions as $option)
                            <option value="{{ $option['value'] }}" @selected($currentPeriod['month'] === $option['value'])>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    <select name="year" class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                        @foreach ($yearOptions as $yearOption)
                            <option value="{{ $yearOption }}" @selected($currentPeriod['year'] === $yearOption)>{{ $yearOption }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700">
                        Tampilkan
                    </button>
                </form>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Periode Aktif</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $periodLabel }}</p>
                <p class="mt-2 text-sm text-slate-500">Filter aktif untuk data lembar kontrol.</p>
            </article>
            <article class="rounded-3xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm">
                <p class="text-sm font-medium text-emerald-700">Operasional Tercatat</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">Rp {{ number_format($summary['operationalTotal'], 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">Beban operasional periode aktif.</p>
            </article>
            <article class="rounded-3xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white p-5 shadow-sm">
                <p class="text-sm font-medium text-violet-700">Jumlah Transaksi</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $summary['totalCount'] }}</p>
                <p class="mt-2 text-sm text-slate-500">Total baris operasional yang tersimpan pada periode ini.</p>
            </article>
            <article class="rounded-3xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm">
                <p class="text-sm font-medium text-amber-700">Sudah Dibayar</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">Rp {{ number_format($summary['settledDebtTotal'], 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">Total hutang talangan yang sudah tertutup.</p>
            </article>
            <article class="rounded-3xl border border-rose-200 bg-gradient-to-br from-rose-50 to-white p-5 shadow-sm">
                <p class="text-sm font-medium text-rose-700">Hutang Aktif</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">Rp {{ number_format($summary['activeDebtTotal'], 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $summary['pendingCount'] }} transaksi talangan belum lunas.</p>
            </article>
        </section>

        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-sm font-medium text-sky-600">Daftar Lembar Kontrol</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Tabel transaksi harian {{ $periodLabel }}</h2>
                    <p class="mt-2 text-sm text-slate-500">Semua data di tabel ini sudah dibedakan antara operasional langsung dan transaksi talangan.</p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('add-data-kontrol', ['month' => $currentPeriod['month'], 'year' => $currentPeriod['year']]) }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700">
                        Tambah Data Kontrol
                    </a>
                    <form action="{{ route('lembar-kontrol.destroy-period') }}" method="POST" onsubmit="return confirm('Hapus semua data lembar kontrol untuk {{ $periodLabel }}? Tindakan ini tidak bisa dibatalkan.');">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="month" value="{{ $currentPeriod['month'] }}">
                        <input type="hidden" name="year" value="{{ $currentPeriod['year'] }}">
                        <button type="submit" @disabled($entries->isEmpty()) class="inline-flex items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 px-5 py-3 text-sm font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100 disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-400">
                            Hapus Semua Data Bulan Ini
                        </button>
                    </form>
                </div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Sumber Dominan</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ $dominantSource ?? 'Belum ada data' }}</p>
                    <p class="mt-1 text-sm text-slate-500">Sumber dengan nominal terbesar pada periode aktif.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Lokasi Terbanyak</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ $dominantLocation ?? 'Belum ada data' }}</p>
                    <p class="mt-1 text-sm text-slate-500">Lokasi yang paling sering muncul pada transaksi.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Jenis Terbanyak</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ $dominantType ? ($typeLabels[$dominantType] ?? $dominantType) : 'Belum ada data' }}</p>
                    <p class="mt-1 text-sm text-slate-500">Jenis transaksi yang paling sering dipakai.</p>
                </div>
            </div>

            <div class="mt-8 overflow-hidden rounded-3xl border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-[1850px] divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-950 text-left text-slate-200">
                            <tr>
                                <th class="px-5 py-4 font-medium">No</th>
                                <th class="px-5 py-4 font-medium">Hari, Tanggal</th>
                                <th class="px-5 py-4 font-medium">Jenis Transaksi</th>
                                <th class="px-5 py-4 font-medium">Operasional</th>
                                <th class="px-5 py-4 font-medium">Arus Masuk</th>
                                <th class="px-5 py-4 font-medium">Sudah Dibayar</th>
                                <th class="px-5 py-4 font-medium">Sisa Hutang</th>
                                <th class="px-5 py-4 font-medium">Pihak Talangan</th>
                                <th class="px-5 py-4 font-medium">Pihak Ke-3</th>
                                <th class="px-5 py-4 font-medium">Petugas</th>
                                <th class="px-5 py-4 font-medium">Pejabat</th>
                                <th class="px-5 py-4 font-medium">Waktu</th>
                                <th class="px-5 py-4 font-medium">Lokasi</th>
                                <th class="px-5 py-4 font-medium">Tujuan / Keperluan</th>
                                <th class="px-5 py-4 font-medium">Sumber Dana</th>
                                <th class="px-5 py-4 font-medium">Status</th>
                                <th class="px-5 py-4 font-medium">Bukti</th>
                                <th class="px-5 py-4 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($entries as $entry)
                                @php
                                    $settledAmount = $entry->settledAmount();
                                    $remainingDebt = $entry->remainingDebt();
                                @endphp
                                <tr class="align-top transition hover:bg-slate-50/80">
                                    <td class="px-5 py-4 font-semibold text-slate-700">{{ $loop->iteration }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ optional($entry->entry_date)->translatedFormat('l, d M Y') }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                            {{ $entry->transactionTypeLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 font-medium text-slate-900">Rp {{ number_format($entry->obligation_amount, 0, ',', '.') }}</td>
                                    <td class="px-5 py-4 text-violet-700">Rp {{ number_format($entry->amount_in, 0, ',', '.') }}</td>
                                    <td class="px-5 py-4 text-sky-700">Rp {{ number_format($settledAmount, 0, ',', '.') }}</td>
                                    <td class="px-5 py-4 font-medium {{ $remainingDebt > 0 ? 'text-rose-700' : 'text-emerald-700' }}">
                                        Rp {{ number_format($entry->transaction_type === 'operasional_talangan' ? $remainingDebt : 0, 0, ',', '.') }}
                                    </td>
                                    <td class="px-5 py-4 text-slate-600">{{ $entry->financier_name ?: '-' }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $entry->third_party ?: '-' }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $entry->receiving_officer }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $entry->appointed_official }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $entry->handover_time }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $entry->location }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $entry->purpose }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $entry->fund_source }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $statusStyles[$entry->status] ?? 'bg-slate-50 text-slate-700 ring-slate-200' }}">
                                            {{ $entry->status }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-slate-600">{{ $entry->proof_original_name ?: 'Belum ada bukti' }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-col gap-2 sm:flex-row">
                                            <a href="{{ route('lembar-kontrol.edit', $entry) }}" class="inline-flex items-center justify-center rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-semibold text-sky-700 transition hover:border-sky-300 hover:bg-sky-100">
                                                Edit
                                            </a>
                                            <form action="{{ route('lembar-kontrol.destroy', $entry) }}" method="POST" onsubmit="return confirm('Hapus data kontrol ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="18" class="px-5 py-10 text-center text-slate-500">
                                        Belum ada data kontrol untuk periode {{ $periodLabel }}.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</x-layout>
