<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    @php
        $accentStyles = [
            'sky' => 'from-sky-500/15 to-sky-100 border-sky-200 text-sky-600',
            'emerald' => 'from-emerald-500/15 to-emerald-100 border-emerald-200 text-emerald-600',
            'amber' => 'from-amber-500/15 to-amber-100 border-amber-200 text-amber-600',
            'rose' => 'from-rose-500/15 to-rose-100 border-rose-200 text-rose-600',
            'teal' => 'from-teal-500/15 to-teal-100 border-teal-200 text-teal-600',
        ];
    @endphp

    <div class="space-y-6">
        <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-medium text-sky-600">Filter Periode</p>
                    <h2 class="mt-1 text-xl font-semibold text-slate-900">Pilih bulan rekap</h2>
                    <p class="mt-1 text-sm text-slate-500">Dashboard, lembar kontrol, dan dana saving akan membaca data sesuai bulan dan tahun yang kamu pilih.</p>
                </div>
                <form method="GET" action="{{ route('dashboard') }}" data-auto-submit-filter class="grid gap-3 sm:grid-cols-[minmax(0,220px)_minmax(0,180px)]">
                    <select name="month" data-auto-submit-control class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                        @foreach ($monthOptions as $option)
                            <option value="{{ $option['value'] }}" @selected($currentPeriod['month'] === $option['value'])>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                    <select name="year" data-auto-submit-control class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                        @foreach ($yearOptions as $yearOption)
                            <option value="{{ $yearOption }}" @selected($currentPeriod['year'] === $yearOption)>{{ $yearOption }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.7fr_1fr]">
            <div class="overflow-hidden rounded-[28px] bg-gradient-to-br from-slate-950 via-slate-900 to-sky-950 p-6 text-white shadow-xl shadow-slate-900/15 sm:p-8">
                <div class="space-y-6">
                    <div class="max-w-2xl">
                        <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium tracking-[0.22em] text-sky-100 uppercase">
                            Rekap Bulanan
                        </span>
                        <h2 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">
                            Dashboard rekap {{ $periodLabel }} untuk tabel kontrol dan saving.
                        </h2>
                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <a href="{{ route('lembar-kontrol', ['month' => $currentPeriod['month'], 'year' => $currentPeriod['year']]) }}" class="inline-flex items-center justify-center rounded-2xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-sky-100">
                                Lihat Lembar Kontrol
                            </a>
                            <a href="{{ route('report') }}" class="inline-flex items-center justify-center rounded-2xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10">
                                Buka Report
                            </a>
                            @if (auth()->user()->isAdmin())
                                <a href="{{ route('users.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10">
                                    Kelola User
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Transaksi</p>
                            <p class="mt-2 text-2xl font-semibold">{{ $stats['transactionCount'] }}</p>
                            <p class="mt-1 text-xs text-slate-300">Data {{ $periodLabel }} yang terbaca</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Sumber Aktif</p>
                            <p class="mt-2 text-2xl font-semibold">{{ $stats['activeFundSources'] }}</p>
                            <p class="mt-1 text-xs text-slate-300">Sumber dana dengan transaksi</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-4 backdrop-blur">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Sumber Tertinggi</p>
                            <p class="mt-2 text-lg font-semibold">{{ $stats['topSource'] }}</p>
                            <p class="mt-1 text-xs text-slate-300">Rp {{ number_format($stats['topSourceAmount'], 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 grid gap-4 md:grid-cols-2">
                    <div class="rounded-3xl border border-white/10 bg-white/8 p-5 backdrop-blur">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-300">Status Saving terhadap Pemakaian Keseluruhan</span>
                            <span class="font-medium {{ $tableTwo['ending_balance'] >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">
                                Rp {{ number_format($tableTwo['ending_balance'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="mt-3 h-3 overflow-hidden rounded-full bg-white/10">
                            <div class="h-full rounded-full bg-gradient-to-r from-cyan-300 via-sky-400 to-emerald-300" style="width: {{ min(100, max(10, (int) round(($tableTwo['overall_usage'] / max($tableTwo['total_saving'], 1)) * 100))) }}%"></div>
                        </div>
                        <p class="mt-3 text-sm text-slate-300">Tabel 2 membandingkan total dana saving dengan pemakaian keseluruhan pada {{ $periodLabel }}.</p>
                    </div>

                    <div class="rounded-3xl border border-white/10 bg-white/8 p-5 backdrop-blur">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-300">Status Saving terhadap Pemakaian Saving</span>
                            <span class="font-medium {{ $tableThree['ending_balance'] >= 0 ? 'text-emerald-300' : 'text-rose-300' }}">
                                Rp {{ number_format($tableThree['ending_balance'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div>
                                <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Saving Aktif</p>
                                <p class="mt-1 text-lg font-semibold">{{ $stats['savingSourcesActive'] }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Pengurangan Saving</p>
                                <p class="mt-1 text-lg font-semibold">{{ $stats['reductionCount'] }}</p>
                            </div>
                            <div>
                                <p class="text-xs uppercase tracking-[0.16em] text-slate-400">Tabel 3</p>
                                <p class="mt-1 text-lg font-semibold">Rp {{ number_format($tableThree['saving_usage'], 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                @foreach ($summaryCards as $card)
                    <article class="rounded-3xl border bg-gradient-to-br p-5 shadow-sm {{ $accentStyles[$card['accent']] ?? 'from-slate-200 to-white border-slate-200 text-slate-700' }}">
                        <p class="text-sm font-medium text-slate-600">{{ $card['label'] }}</p>
                        <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">Rp {{ number_format($card['amount'], 0, ',', '.') }}</p>
                        <div class="mt-4 inline-flex rounded-full bg-white/70 px-3 py-1 text-xs font-medium text-slate-700">
                            {{ $card['caption'] }}
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="space-y-6">
            <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-sky-600">Tabel 1</p>
                        <h3 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Rekap operasional sesuai sumber dana</h3>
                        <p class="mt-2 text-sm text-slate-500">Nominal pada tabel ini dihitung langsung dari data lembar kontrol pada periode aktif.</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600 ring-1 ring-slate-200">
                        <p class="font-medium text-slate-900">{{ $periodLabel }}</p>
                        <p class="mt-1 text-lg font-semibold text-sky-600">Rp {{ number_format($tableOneTotals['total'], 0, ',', '.') }}</p>
                    </div>
                </div>

                <div class="mt-6 overflow-hidden rounded-3xl border border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-950 text-left text-slate-200">
                                <tr>
                                    <th class="px-5 py-4 font-medium">Keterangan</th>
                                    <th class="px-5 py-4 font-medium">Hutang</th>
                                    <th class="px-5 py-4 font-medium">Lunas</th>
                                    <th class="px-5 py-4 font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($tableOne as $row)
                                    <tr>
                                        <td class="px-5 py-4 font-semibold text-slate-900">{{ $row['source'] }}</td>
                                        <td class="px-5 py-4 text-rose-600">Rp {{ number_format($row['hutang'], 0, ',', '.') }}</td>
                                        <td class="px-5 py-4 text-emerald-600">Rp {{ number_format($row['lunas'], 0, ',', '.') }}</td>
                                        <td class="px-5 py-4 font-semibold text-slate-900">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-slate-50 text-slate-900">
                                <tr>
                                    <td class="px-5 py-4 font-semibold">TOTAL</td>
                                    <td class="px-5 py-4 font-semibold text-rose-600">Rp {{ number_format($tableOneTotals['hutang'], 0, ',', '.') }}</td>
                                    <td class="px-5 py-4 font-semibold text-emerald-600">Rp {{ number_format($tableOneTotals['lunas'], 0, ',', '.') }}</td>
                                    <td class="px-5 py-4 font-semibold">Rp {{ number_format($tableOneTotals['total'], 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </article>

            <div class="grid gap-6 xl:grid-cols-2">
                <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-emerald-600">Tabel 2</p>
                    <h3 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Status dana saving terhadap pemakaian keseluruhan</h3>

                    <div class="mt-5 space-y-3">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Total Dana Saving</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">Rp {{ number_format($tableTwo['total_saving'], 0, ',', '.') }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Pemakaian Dana Keseluruhan</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">Rp {{ number_format($tableTwo['overall_usage'], 0, ',', '.') }}</p>
                        </div>
                        <div class="rounded-2xl border {{ $tableTwo['ending_balance'] >= 0 ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }} p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Saldo Akhir</p>
                            <p class="mt-2 text-2xl font-semibold {{ $tableTwo['ending_balance'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">Rp {{ number_format($tableTwo['ending_balance'], 0, ',', '.') }}</p>
                        </div>
                    </div>

                </article>

                <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-violet-600">Tabel 3</p>
                    <h3 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Status dana saving terhadap pemakaian dana saving</h3>

                    <div class="mt-5 grid gap-3">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Total Dana Saving</p>
                            <p class="mt-2 text-xl font-semibold text-slate-900">Rp {{ number_format($tableThree['total_saving'], 0, ',', '.') }}</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Pemakaian Dana Saving</p>
                            <p class="mt-2 text-xl font-semibold text-slate-900">Rp {{ number_format($tableThree['saving_usage'], 0, ',', '.') }}</p>
                        </div>
                        <div class="rounded-2xl border {{ $tableThree['ending_balance'] >= 0 ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }} p-4">
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-500">Saldo Akhir</p>
                            <p class="mt-2 text-xl font-semibold {{ $tableThree['ending_balance'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">Rp {{ number_format($tableThree['ending_balance'], 0, ',', '.') }}</p>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[0.92fr_1.08fr]">
            <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-amber-600">Tabel 2.1</p>
                        <h3 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Dana saving</h3>
                    </div>
                    @if ($canManageSaving)
                        <a href="{{ route('dana-saving.index', ['month' => $currentPeriod['month'], 'year' => $currentPeriod['year']]) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-sky-300 hover:text-sky-700">
                            Kelola Dana Saving
                        </a>
                    @endif
                </div>
                <div class="mt-6 space-y-4">
                    @forelse ($tableTwoOne as $row)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $row['source'] }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $row['entries_count'] }} kali penambahan manual pada {{ $periodLabel }}</p>
                                    @if ($row['carry_over_amount'] !== 0 && $row['carry_over_label'])
                                        <p class="mt-1 text-xs font-medium {{ $row['carry_over_amount'] >= 0 ? 'text-sky-700' : 'text-rose-700' }}">{{ $row['carry_over_label'] }} Rp {{ number_format($row['carry_over_amount'], 0, ',', '.') }}</p>
                                    @endif
                                </div>
                                <p class="text-lg font-semibold text-slate-900">Rp {{ number_format($row['amount'], 0, ',', '.') }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-5 text-sm text-slate-500">
                            Belum ada dana saving untuk periode {{ $periodLabel }}.
                        </div>
                    @endforelse
                </div>
            </article>

            <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-rose-600">Tabel 2.2</p>
                        <h3 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Dana saving terpakai</h3>
                        <p class="mt-2 text-sm text-slate-500">Blok ini memperlihatkan alokasi saving, pemakaian langsung dari lembar kontrol, lalu sisa atau kurangnya per sumber dana.</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-600 ring-1 ring-slate-200">
                        <p class="font-medium text-slate-900">Total Terpakai</p>
                        <p class="mt-1 text-lg font-semibold text-rose-600">Rp {{ number_format($tableThree['saving_usage'], 0, ',', '.') }}</p>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse ($tableTwoTwo as $row)
                        <div class="rounded-3xl border border-slate-200 p-4">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $row['source'] }}</p>
                                    <p class="mt-1 text-sm text-slate-500">Alokasi Rp {{ number_format($row['allocation'], 0, ',', '.') }}</p>
                                    @if ($row['carry_over_amount'] !== 0 && $row['carry_over_label'])
                                        <p class="mt-1 text-xs font-medium {{ $row['carry_over_amount'] >= 0 ? 'text-sky-700' : 'text-rose-700' }}">{{ $row['carry_over_label'] }} Rp {{ number_format($row['carry_over_amount'], 0, ',', '.') }}</p>
                                    @endif
                                </div>
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Saving</p>
                                        <p class="mt-1 font-semibold text-slate-900">Rp {{ number_format($row['allocation'], 0, ',', '.') }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Terpakai</p>
                                        <p class="mt-1 font-semibold text-slate-900">Rp {{ number_format($row['used'], 0, ',', '.') }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Langsung Rp {{ number_format($row['direct_used'], 0, ',', '.') }}</p>
                                    </div>
                                    <div class="rounded-2xl {{ $row['balance'] >= 0 ? 'bg-emerald-50' : 'bg-rose-50' }} px-4 py-3">
                                        <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Sisa / Kurang</p>
                                        <p class="mt-1 font-semibold {{ $row['balance'] >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">Rp {{ number_format($row['balance'], 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-gradient-to-r from-sky-500 to-cyan-300" style="width: {{ min(100, max(0, $row['usage_percent'])) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-5 text-sm text-slate-500">
                            Belum ada data alokasi saving untuk dihitung pada {{ $periodLabel }}.
                        </div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
            <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-sky-600">Rincian Saving</p>
                        <h3 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Filter pemakaian dana saving</h3>
                        <p class="mt-2 text-sm text-slate-500">Bagian ini menampilkan transaksi {{ $periodLabel }} yang memakai sumber dana saving.</p>
                    </div>
                    <a href="{{ route('add-data-kontrol', ['month' => $currentPeriod['month'], 'year' => $currentPeriod['year']]) }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700">
                        Tambah Data
                    </a>
                </div>

                <div class="mt-6 overflow-hidden rounded-3xl border border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-slate-500">
                                <tr>
                                    <th class="px-5 py-4 font-medium">Tanggal</th>
                                    <th class="px-5 py-4 font-medium">Tujuan</th>
                                    <th class="px-5 py-4 font-medium">Sumber Dana</th>
                                    <th class="px-5 py-4 font-medium">Nominal</th>
                                    <th class="px-5 py-4 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse ($savingEntries as $entry)
                                    <tr>
                                        <td class="px-5 py-4 font-medium text-slate-700">{{ $entry['date'] }}</td>
                                        <td class="px-5 py-4 text-slate-600">{{ $entry['purpose'] }}</td>
                                        <td class="px-5 py-4 text-slate-600">{{ $entry['source'] }}</td>
                                        <td class="px-5 py-4 font-semibold text-slate-900">Rp {{ number_format($entry['amount'], 0, ',', '.') }}</td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                                {{ $entry['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-8 text-center text-slate-500">Belum ada transaksi saving yang terbaca pada periode {{ $periodLabel }}.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </article>

            <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-violet-600">Aktivitas Terakhir</p>
                <h3 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Transaksi terbaru {{ $periodLabel }}</h3>

                <div class="mt-5 space-y-4">
                    @forelse ($latestEntries as $entry)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $entry['purpose'] }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $entry['date'] }} | {{ $entry['source'] }}</p>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                                    {{ $entry['status'] }}
                                </span>
                            </div>
                            <p class="mt-3 text-lg font-semibold text-slate-900">Rp {{ number_format($entry['amount'], 0, ',', '.') }}</p>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-5 text-sm text-slate-500">
                            Belum ada transaksi {{ $periodLabel }} yang tersimpan di database.
                        </div>
                    @endforelse
                </div>
            </article>
        </section>
    </div>
</x-layout>
