<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    <div class="space-y-6">
        <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-medium text-sky-600">Filter Periode</p>
                    <h2 class="mt-1 text-xl font-semibold text-slate-900">Dana saving per bulan</h2>
                    <p class="mt-1 text-sm text-slate-500">Rekap dan histori penambahan saving akan mengikuti bulan dan tahun yang dipilih.</p>
                </div>
                <form method="GET" action="{{ route('dana-saving.index') }}" data-auto-submit-filter class="grid gap-3 sm:grid-cols-[minmax(0,220px)_minmax(0,180px)_auto]">
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
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700">
                        Tampilkan
                    </button>
                </form>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Periode Aktif</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $periodLabel }}</p>
                <p class="mt-2 text-sm text-slate-500">Periode saving yang sedang dipakai dashboard.</p>
            </article>
            <article class="rounded-3xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5 shadow-sm">
                <p class="text-sm font-medium text-emerald-700">Total Dana Saving</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">Rp {{ number_format($summary['total'], 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">Akumulasi nominal saving aktif.</p>
            </article>
            <article class="rounded-3xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-5 shadow-sm">
                <p class="text-sm font-medium text-sky-700">Riwayat Penambahan</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $summary['count'] }}</p>
                <p class="mt-2 text-sm text-slate-500">Total baris penambahan saving yang tersimpan.</p>
            </article>
            <article class="rounded-3xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-5 shadow-sm">
                <p class="text-sm font-medium text-amber-700">Sumber Saving Aktif</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $summary['activeSources'] }}</p>
                <p class="mt-2 text-sm text-slate-500">Jumlah sumber saving aktif pada periode ini.</p>
            </article>
        </section>

        <section class="grid gap-4 md:grid-cols-2">
            <article class="rounded-3xl border border-rose-200 bg-gradient-to-br from-rose-50 to-white p-5 shadow-sm">
                <p class="text-sm font-medium text-rose-700">Total Pengurangan Saving</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">Rp {{ number_format($summary['reductionTotal'], 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $summary['reductionCount'] }} baris pengurangan tersimpan. Angka ini tidak mengubah hitungan dashboard.</p>
            </article>
            <article class="rounded-3xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white p-5 shadow-sm">
                <p class="text-sm font-medium text-violet-700">Sisa Kewajiban Bayar Balik</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">Rp {{ number_format($summary['remainingReimbursement'], 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">Menggambarkan saving pribadi yang masih perlu dibayarkan kembali pada periode ini.</p>
            </article>
        </section>

        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-sm font-medium text-sky-600">Tabel 2.1</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Master dana saving {{ $periodLabel }}</h2>
                    <p class="mt-2 text-sm text-slate-500">Penambahan saving tetap dibaca dashboard seperti biasa. Pengurangan saving dicatat terpisah khusus untuk memantau kewajiban bayar balik.</p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('dana-saving.reductions.create', ['month' => $currentPeriod['month'], 'year' => $currentPeriod['year']]) }}" class="inline-flex items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 px-5 py-3 text-sm font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100">
                        Kurangi Dana Saving
                    </a>
                    <a href="{{ route('dana-saving.create', ['month' => $currentPeriod['month'], 'year' => $currentPeriod['year']]) }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700">
                        Tambah Dana Saving
                    </a>
                </div>
            </div>

            <div class="mt-6 grid gap-4 lg:grid-cols-2">
                @forelse ($groupedSummary as $item)
                    <article class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $item['source'] }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $item['entries'] }} kali penambahan manual</p>
                                @if ($item['carry_over_amount'] !== 0 && $item['carry_over_label'])
                                    <p class="mt-1 text-xs font-medium {{ $item['carry_over_amount'] >= 0 ? 'text-sky-700' : 'text-rose-700' }}">{{ $item['carry_over_label'] }} Rp {{ number_format($item['carry_over_amount'], 0, ',', '.') }}</p>
                                @endif
                                @if (($item['transferred_to_next'] ?? 0) > 0 && ! empty($item['transferred_to_next_label']))
                                    <p class="mt-1 text-xs font-medium text-amber-700">{{ $item['transferred_to_next_label'] }} Rp {{ number_format($item['transferred_to_next'], 0, ',', '.') }}</p>
                                @endif
                                <p class="mt-1 text-xs text-rose-600">Sudah dikurangi: Rp {{ number_format($item['reduced'], 0, ',', '.') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-semibold text-slate-900">Rp {{ number_format($item['total'], 0, ',', '.') }}</p>
                                <p class="mt-1 text-xs font-medium text-violet-700">Sisa bayar balik Rp {{ number_format($item['remaining_reimbursement'], 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 p-5 text-sm text-slate-500 lg:col-span-2">
                        Belum ada data saving pada periode {{ $periodLabel }}.
                    </div>
                @endforelse
            </div>

            <div class="mt-8 overflow-hidden rounded-3xl border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-950 text-left text-slate-200">
                            <tr>
                                <th class="px-5 py-4 font-medium">Tanggal Input</th>
                                <th class="px-5 py-4 font-medium">Posisi Dashboard</th>
                                <th class="px-5 py-4 font-medium">Sumber Dana Saving</th>
                                <th class="px-5 py-4 font-medium">Periode</th>
                                <th class="px-5 py-4 font-medium">Nominal</th>
                                <th class="px-5 py-4 font-medium">Status</th>
                                <th class="px-5 py-4 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($allocationRows as $row)
                                <tr class="align-top transition hover:bg-slate-50/80">
                                    <td class="px-5 py-4 text-slate-600">{{ $row['type'] === 'carry_over' ? '-' : optional($row['created_at'])->format('d M Y H:i') }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-700">{{ $row['sort_order'] }}</td>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-slate-900">{{ $row['source_name'] }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $row['type'] === 'carry_over' ? $row['label'] : 'Riwayat penambahan saving manual' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-slate-600">{{ sprintf('%02d', $row['period_month']) }}/{{ $row['period_year'] }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-900">Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $row['type'] === 'carry_over' ? 'bg-sky-50 text-sky-700 ring-sky-200' : ($row['is_active'] ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-slate-200') }}">
                                            {{ $row['type'] === 'carry_over' ? 'Otomatis' : ($row['is_active'] ? 'Aktif' : 'Nonaktif') }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        @if ($row['type'] === 'carry_over')
                                            <span class="text-xs font-medium text-slate-500">Otomatis dari sisa periode sebelumnya</span>
                                        @else
                                            <div class="flex flex-col gap-2 sm:flex-row">
                                                <a href="{{ route('dana-saving.edit', $row['model']) }}" class="inline-flex items-center justify-center rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-semibold text-sky-700 transition hover:border-sky-300 hover:bg-sky-100">
                                                    Edit
                                                </a>
                                                <form action="{{ route('dana-saving.destroy', $row['model']) }}" method="POST" onsubmit="return confirm('Hapus data dana saving ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-slate-500">
                                        Belum ada data dana saving untuk periode {{ $periodLabel }}.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-sm font-medium text-rose-600">Jendela Pengurangan</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Riwayat pengurangan dana saving {{ $periodLabel }}</h2>
                    <p class="mt-2 text-sm text-slate-500">Bagian ini dipakai untuk mencatat pembayaran balik dana pribadi tanpa mengurangi total saving yang dipakai dashboard.</p>
                </div>

                <a href="{{ route('dana-saving.reductions.create', ['month' => $currentPeriod['month'], 'year' => $currentPeriod['year']]) }}" class="inline-flex items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 px-5 py-3 text-sm font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100">
                    Tambah Pengurangan
                </a>
            </div>

            <div class="mt-6 overflow-hidden rounded-3xl border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-950 text-left text-slate-200">
                            <tr>
                                <th class="px-5 py-4 font-medium">Tanggal</th>
                                <th class="px-5 py-4 font-medium">Sumber Dana Saving</th>
                                <th class="px-5 py-4 font-medium">Periode</th>
                                <th class="px-5 py-4 font-medium">Nominal Pengurangan</th>
                                <th class="px-5 py-4 font-medium">Catatan</th>
                                <th class="px-5 py-4 font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($reductions as $reduction)
                                <tr class="align-top transition hover:bg-slate-50/80">
                                    <td class="px-5 py-4 text-slate-600">{{ optional($reduction->reduction_date)->format('d M Y') }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-900">{{ $reduction->source_name }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ sprintf('%02d', $reduction->period_month) }}/{{ $reduction->period_year }}</td>
                                    <td class="px-5 py-4 font-semibold text-rose-700">Rp {{ number_format($reduction->amount, 0, ',', '.') }}</td>
                                    <td class="px-5 py-4 text-slate-600">{{ $reduction->note ?: '-' }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-col gap-2 sm:flex-row">
                                            <a href="{{ route('dana-saving.reductions.edit', $reduction) }}" class="inline-flex items-center justify-center rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-semibold text-sky-700 transition hover:border-sky-300 hover:bg-sky-100">
                                                Edit
                                            </a>
                                            <form action="{{ route('dana-saving.reductions.destroy', $reduction) }}" method="POST" onsubmit="return confirm('Hapus data pengurangan saving ini?')">
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
                                    <td colspan="6" class="px-5 py-10 text-center text-slate-500">
                                        Belum ada data pengurangan dana saving untuk periode {{ $periodLabel }}.
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
