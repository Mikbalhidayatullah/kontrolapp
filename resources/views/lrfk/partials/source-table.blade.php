@php
    $isPerubahan = $selectedVersion === \App\Models\LrfkEntry::DATASET_PERUBAHAN;
    $money = static fn ($value): string => number_format((int) $value, 0, ',', '.');
    $percent = static fn ($value): string => number_format((float) $value, 2, ',', '.').'%';
    $variance = static fn ($row): string => filled($row->variance_note)
        ? (string) $row->variance_note
        : number_format((int) $row->variance, 0, ',', '.');
@endphp

<div class="overflow-hidden rounded-3xl border border-slate-200">
    <div class="overflow-x-auto">
        <table class="lrfk-table min-w-[2200px] divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-950 text-left text-slate-200">
                <tr>
                    <th class="px-4 py-3 font-medium">Kode</th>
                    <th class="px-4 py-3 font-medium">Kode Rekening</th>
                    <th class="px-4 py-3 font-medium">Program / Kegiatan / Sub Kegiatan</th>
                    <th class="px-4 py-3 text-right font-medium">Pagu Anggaran</th>
                    <th class="px-4 py-3 text-right font-medium">Kontrak Nilai</th>
                    @unless ($isPerubahan)
                        <th class="px-4 py-3 font-medium">Nomor / Tanggal</th>
                    @endunless
                    <th class="px-4 py-3 font-medium">Pelaksana</th>
                    <th class="px-4 py-3 font-medium">Keluaran</th>
                    <th class="px-4 py-3 font-medium">Volume</th>
                    <th class="px-4 py-3 font-medium">Satuan</th>
                    <th class="px-4 py-3 text-right font-medium">Realisasi Keuangan</th>
                    <th class="px-4 py-3 text-right font-medium">Keuangan %</th>
                    <th class="px-4 py-3 text-right font-medium">Fisik %</th>
                    @if ($isPerubahan)
                        <th class="px-4 py-3 text-right font-medium">Sisa Pagu Anggaran</th>
                        <th class="px-4 py-3 text-right font-medium">Oktober</th>
                        <th class="px-4 py-3 text-right font-medium">November</th>
                        <th class="px-4 py-3 text-right font-medium">Desember</th>
                        <th class="px-4 py-3 text-right font-medium">Triwulan IV</th>
                    @endif
                    <th class="px-4 py-3 font-medium">Lokasi</th>
                    <th class="px-4 py-3 font-medium">Ket.</th>
                    <th class="px-4 py-3 font-medium">Selisih</th>
                    <th class="px-4 py-3 font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($entries as $entry)
                    <tr data-lrfk-level="{{ $entry->level }}" data-lrfk-source-row="{{ $entry->source_row }}">
                        <td class="px-4 py-4 align-top font-semibold">{{ $entry->kode ?: '-' }}</td>
                        <td class="px-4 py-4 align-top font-medium">{{ $entry->kode_rekening ?: '-' }}</td>
                        <td class="max-w-md px-4 py-4 align-top">
                            <p class="whitespace-pre-line font-semibold">{{ $entry->program_kegiatan }}</p>
                            <p class="mt-1 text-xs opacity-70">{{ $levelOptions[$entry->level] ?? $entry->level }}</p>
                        </td>
                        <td class="px-4 py-4 text-right align-top font-semibold">Rp {{ $money($entry->pagu_anggaran) }}</td>
                        <td class="px-4 py-4 text-right align-top">Rp {{ $money($entry->contract_value) }}</td>
                        @unless ($isPerubahan)
                            <td class="max-w-sm whitespace-pre-line px-4 py-4 align-top">{{ $entry->contract_number_date ?: '-' }}</td>
                        @endunless
                        <td class="max-w-sm whitespace-pre-line px-4 py-4 align-top">{{ $entry->implementer ?: '-' }}</td>
                        <td class="max-w-md whitespace-pre-line px-4 py-4 align-top">{{ $entry->output ?: '-' }}</td>
                        <td class="whitespace-pre-line px-4 py-4 align-top">{{ $entry->volume ?: '-' }}</td>
                        <td class="whitespace-pre-line px-4 py-4 align-top">{{ $entry->unit ?: '-' }}</td>
                        <td class="px-4 py-4 text-right align-top font-semibold">Rp {{ $money($entry->financial_realization) }}</td>
                        <td class="px-4 py-4 text-right align-top">{{ $percent($entry->financial_percent) }}</td>
                        <td class="px-4 py-4 text-right align-top">{{ $percent($entry->physical_percent) }}</td>
                        @if ($isPerubahan)
                            <td class="px-4 py-4 text-right align-top">Rp {{ $money($entry->budget_balance) }}</td>
                            <td class="px-4 py-4 text-right align-top">Rp {{ $money($entry->cash_plan_october) }}</td>
                            <td class="px-4 py-4 text-right align-top">Rp {{ $money($entry->cash_plan_november) }}</td>
                            <td class="px-4 py-4 text-right align-top">Rp {{ $money($entry->cash_plan_december) }}</td>
                            <td class="px-4 py-4 text-right align-top">Rp {{ $money($entry->cash_plan_quarter) }}</td>
                        @endif
                        <td class="max-w-sm whitespace-pre-line px-4 py-4 align-top">{{ $entry->location ?: '-' }}</td>
                        <td class="max-w-sm whitespace-pre-line px-4 py-4 align-top">{{ $entry->notes ?: '-' }}</td>
                        <td class="max-w-sm whitespace-pre-line px-4 py-4 align-top">{{ $variance($entry) }}</td>
                        <td class="px-4 py-4 align-top">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('lrfk.edit', ['lrfkEntry' => $entry, 'version' => $selectedVersion]) }}" class="inline-flex rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 transition hover:bg-sky-100">
                                    Edit
                                </a>
                                <form action="{{ route('lrfk.destroy', $entry) }}" method="POST" onsubmit="return confirm('Hapus data LRFK ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="version" value="{{ $selectedVersion }}" />
                                    <button type="submit" class="inline-flex rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    @foreach ($entry->details as $detail)
                        <tr data-lrfk-detail="{{ $detail->source_row }}" class="bg-white text-slate-700">
                            <td class="px-4 py-4 align-top"></td>
                            <td class="px-4 py-4 align-top"></td>
                            <td class="px-4 py-4 align-top"></td>
                            <td class="px-4 py-4 align-top"></td>
                            <td class="px-4 py-4 text-right align-top">Rp {{ $money($detail->contract_value) }}</td>
                            @unless ($isPerubahan)
                                <td class="max-w-sm whitespace-pre-line px-4 py-4 align-top">{{ $detail->contract_number_date ?: '-' }}</td>
                            @endunless
                            <td class="max-w-sm whitespace-pre-line px-4 py-4 align-top">{{ $detail->implementer ?: '-' }}</td>
                            <td class="max-w-md whitespace-pre-line px-4 py-4 align-top">{{ $detail->output ?: '-' }}</td>
                            <td class="whitespace-pre-line px-4 py-4 align-top">{{ $detail->volume ?: '-' }}</td>
                            <td class="whitespace-pre-line px-4 py-4 align-top">{{ $detail->unit ?: '-' }}</td>
                            <td class="px-4 py-4 text-right align-top font-semibold">Rp {{ $money($detail->financial_realization) }}</td>
                            <td class="px-4 py-4 text-right align-top">{{ $percent($detail->financial_percent) }}</td>
                            <td class="px-4 py-4 text-right align-top">{{ $percent($detail->physical_percent) }}</td>
                            @if ($isPerubahan)
                                <td class="px-4 py-4 text-right align-top">Rp {{ $money($detail->budget_balance) }}</td>
                                <td class="px-4 py-4 text-right align-top">Rp {{ $money($detail->cash_plan_october) }}</td>
                                <td class="px-4 py-4 text-right align-top">Rp {{ $money($detail->cash_plan_november) }}</td>
                                <td class="px-4 py-4 text-right align-top">Rp {{ $money($detail->cash_plan_december) }}</td>
                                <td class="px-4 py-4 text-right align-top">Rp {{ $money($detail->cash_plan_quarter) }}</td>
                            @endif
                            <td class="max-w-sm whitespace-pre-line px-4 py-4 align-top">{{ $detail->location ?: '-' }}</td>
                            <td class="max-w-sm whitespace-pre-line px-4 py-4 align-top">{{ $detail->notes ?: '-' }}</td>
                            <td class="max-w-sm whitespace-pre-line px-4 py-4 align-top">{{ $variance($detail) }}</td>
                            <td class="px-4 py-4 align-top"></td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>
