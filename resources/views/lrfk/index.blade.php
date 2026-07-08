<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    <style>
        .lrfk-table tbody tr[data-lrfk-level="dinas"] {
            background-color: #ffedd5 !important;
            color: #7c2d12 !important;
        }

        .lrfk-table tbody tr[data-lrfk-level="belanja_daerah"] {
            background-color: #d1fae5 !important;
            color: #065f46 !important;
        }

        .lrfk-table tbody tr[data-lrfk-level="program"] {
            background-color: #e0f2fe !important;
            color: #075985 !important;
        }

        .lrfk-table tbody tr[data-lrfk-level="kegiatan"] {
            background-color: #fce7f3 !important;
            color: #831843 !important;
        }

        .lrfk-table tbody tr[data-lrfk-level="sub_kegiatan"] {
            background-color: #e2e8f0 !important;
            color: #0f172a !important;
        }

        .lrfk-table tbody tr[data-lrfk-level="rekening"] {
            background-color: #ffffff !important;
            color: #0f172a !important;
        }
    </style>

    <div class="space-y-6">
        <section class="grid gap-4 lg:grid-cols-4">
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Total Data</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $summary['count'] }}</p>
                <p class="mt-2 text-sm text-slate-500">Baris LRFK pada filter aktif</p>
            </article>
            <article class="rounded-3xl border border-sky-200 bg-sky-50 p-5 shadow-sm">
                <p class="text-sm font-medium text-sky-700">Pagu Anggaran</p>
                <p class="mt-3 text-2xl font-semibold text-slate-900">Rp {{ number_format($summary['pagu'], 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">Total dari level tertinggi filter aktif</p>
            </article>
            <article class="rounded-3xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <p class="text-sm font-medium text-amber-700">Kontrak</p>
                <p class="mt-3 text-2xl font-semibold text-slate-900">Rp {{ number_format($summary['contract'], 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">Nilai kontrak terisi</p>
            </article>
            <article class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                <p class="text-sm font-medium text-emerald-700">Realisasi</p>
                <p class="mt-3 text-2xl font-semibold text-slate-900">Rp {{ number_format($summary['realization'], 0, ',', '.') }}</p>
                <p class="mt-2 text-sm text-slate-500">Realisasi keuangan</p>
            </article>
        </section>

        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-medium text-sky-600">LRFK</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Laporan realisasi fisik dan keuangan</h2>
                    <p class="mt-2 text-sm text-slate-500">Kelola program, kegiatan, sub kegiatan, rekening, kontrak, dan realisasi.</p>
                </div>

                <div class="flex flex-col gap-3">
                    <form action="{{ route('lrfk.index') }}" method="GET" data-auto-submit-filter class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <select name="level" data-auto-submit-control class="rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                            <option value="">Semua jenis</option>
                            @foreach ($levelOptions as $value => $label)
                                <option value="{{ $value }}" @selected($selectedLevel === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="keyword" value="{{ $selectedKeyword }}" placeholder="Cari kode, rekening, kegiatan..." data-auto-submit-control data-auto-submit-delay="450" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100 sm:w-80" />
                        @if ($selectedLevel !== '' || $selectedKeyword !== '')
                            <a href="{{ route('lrfk.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-500 transition hover:border-slate-300 hover:text-slate-700">
                                Reset
                            </a>
                        @endif
                    </form>

                    <div class="flex justify-end">
                        <a href="{{ route('lrfk.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                            Tambah LRFK
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
            @if ($entries->isEmpty())
                <div class="rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                    Belum ada data LRFK pada filter ini.
                </div>
            @else
                <div class="overflow-hidden rounded-3xl border border-slate-200">
                    <div class="overflow-x-auto">
                        <table class="lrfk-table min-w-[1600px] divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-950 text-left text-slate-200">
                                <tr>
                                    <th class="px-4 py-3 font-medium">Kode</th>
                                    <th class="px-4 py-3 font-medium">Kode Rekening</th>
                                    <th class="px-4 py-3 font-medium">Program / Kegiatan / Sub Kegiatan</th>
                                    <th class="px-4 py-3 text-right font-medium">Pagu Anggaran</th>
                                    <th class="px-4 py-3 text-right font-medium">Kontrak Nilai</th>
                                    <th class="px-4 py-3 font-medium">Nomor / Tanggal</th>
                                    <th class="px-4 py-3 font-medium">Pelaksana</th>
                                    <th class="px-4 py-3 font-medium">Keluaran</th>
                                    <th class="px-4 py-3 font-medium">Volume</th>
                                    <th class="px-4 py-3 font-medium">Satuan</th>
                                    <th class="px-4 py-3 text-right font-medium">Realisasi Rp</th>
                                    <th class="px-4 py-3 text-right font-medium">Keuangan %</th>
                                    <th class="px-4 py-3 text-right font-medium">Fisik %</th>
                                    <th class="px-4 py-3 font-medium">Lokasi</th>
                                    <th class="px-4 py-3 font-medium">Ket.</th>
                                    <th class="px-4 py-3 font-medium">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($entries as $entry)
                                    <tr data-lrfk-level="{{ $entry->level }}">
                                        <td class="px-4 py-4 align-top font-semibold">{{ $entry->kode ?: '-' }}</td>
                                        <td class="px-4 py-4 align-top font-medium">{{ $entry->kode_rekening ?: '-' }}</td>
                                        <td class="max-w-md px-4 py-4 align-top">
                                            @if (trim($entry->program_kegiatan) !== '')
                                                <p class="font-semibold">{{ $entry->program_kegiatan }}</p>
                                                <p class="mt-1 text-xs opacity-70">{{ $levelOptions[$entry->level] ?? $entry->level }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-right align-top font-semibold">Rp {{ number_format($entry->pagu_anggaran, 0, ',', '.') }}</td>
                                        <td class="px-4 py-4 text-right align-top">Rp {{ number_format($entry->contract_value, 0, ',', '.') }}</td>
                                        <td class="px-4 py-4 align-top whitespace-pre-line">{{ $entry->contract_number_date ?: '-' }}</td>
                                        <td class="px-4 py-4 align-top whitespace-pre-line">{{ $entry->implementer ?: '-' }}</td>
                                        <td class="max-w-sm px-4 py-4 align-top whitespace-pre-line">{{ $entry->output ?: '-' }}</td>
                                        <td class="px-4 py-4 align-top">{{ $entry->volume ?: '-' }}</td>
                                        <td class="px-4 py-4 align-top">{{ $entry->unit ?: '-' }}</td>
                                        <td class="px-4 py-4 text-right align-top font-semibold">Rp {{ number_format($entry->financial_realization, 0, ',', '.') }}</td>
                                        <td class="px-4 py-4 text-right align-top">{{ number_format((float) $entry->financial_percent, 2, ',', '.') }}%</td>
                                        <td class="px-4 py-4 text-right align-top">{{ number_format((float) $entry->physical_percent, 2, ',', '.') }}%</td>
                                        <td class="px-4 py-4 align-top">{{ $entry->location ?: '-' }}</td>
                                        <td class="max-w-xs px-4 py-4 align-top">{{ $entry->notes ?: '-' }}</td>
                                        <td class="px-4 py-4 align-top">
                                            <div class="flex flex-wrap gap-2">
                                                <a href="{{ route('lrfk.edit', $entry) }}" class="inline-flex rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 transition hover:bg-sky-100">
                                                    Edit
                                                </a>
                                                <form action="{{ route('lrfk.destroy', $entry) }}" method="POST" onsubmit="return confirm('Hapus data LRFK ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </section>
    </div>
</x-layout>
