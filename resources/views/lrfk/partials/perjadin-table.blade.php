@php
    $money = static fn ($value): string => number_format((int) $value, 0, ',', '.');
    $percent = static fn ($value): string => number_format((float) $value, 2, ',', '.').'%';
@endphp

<div class="overflow-hidden rounded-3xl border border-slate-200">
    <div class="overflow-x-auto">
        <table class="lrfk-table min-w-[2100px] divide-y divide-slate-200 text-sm">
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
                    <th class="px-4 py-3 text-right font-medium">Realisasi Keuangan</th>
                    <th class="px-4 py-3 text-right font-medium">Keuangan %</th>
                    <th class="px-4 py-3 text-right font-medium">Fisik %</th>
                    <th class="px-4 py-3 font-medium">Lokasi</th>
                    <th class="px-4 py-3 font-medium">Ket.</th>
                    <th class="px-4 py-3 font-medium">Selisih</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($entries as $entry)
                    @php
                        $rowMetrics = $metrics[$entry->id] ?? ['contract' => 0, 'realization' => 0, 'linked' => 0];
                        $contract = (int) $rowMetrics['contract'];
                        $realization = (int) $rowMetrics['realization'];
                        $hasPerjadin = (int) $rowMetrics['linked'] > 0;
                        $pagu = (int) ($paguByEntry[$entry->id] ?? 0);
                        $percentage = $pagu > 0
                            ? round(($realization / $pagu) * 100, 2)
                            : 0;
                        $linkedUsages = $linkedUsageByEntry[$entry->id] ?? [];
                    @endphp
                    <tr data-lrfk-level="{{ $entry->level }}" data-lrfk-perjadin-row="{{ $entry->id }}">
                        <td class="px-4 py-4 align-top font-semibold">{{ $entry->kode ?: '-' }}</td>
                        <td class="px-4 py-4 align-top font-medium">{{ $entry->kode_rekening ?: '-' }}</td>
                        <td class="max-w-md px-4 py-4 align-top">
                            <p class="font-semibold">{{ $entry->program_kegiatan }}</p>
                            <p class="mt-1 text-xs opacity-70">{{ $levelOptions[$entry->level] ?? $entry->level }}</p>
                        </td>
                        <td class="px-4 py-4 text-right align-top font-semibold">Rp {{ $money($pagu) }}</td>
                        <td class="px-4 py-4 text-right align-top">{{ $hasPerjadin ? 'Rp '.$money($contract) : '' }}</td>
                        <td class="max-w-sm px-4 py-4 align-top">
                            @foreach ($linkedUsages as $usage)
                                <p class="{{ ! $loop->first ? 'mt-2 border-t border-current/10 pt-2' : '' }}">{{ $usage['assignment_number_date'] }}</p>
                            @endforeach
                        </td>
                        <td class="max-w-sm px-4 py-4 align-top">
                            @foreach ($linkedUsages as $usage)
                                <p class="{{ ! $loop->first ? 'mt-2 border-t border-current/10 pt-2' : '' }}">{{ $usage['implementer'] }}</p>
                            @endforeach
                        </td>
                        <td class="max-w-md px-4 py-4 align-top">
                            @foreach ($linkedUsages as $usage)
                                <p class="{{ ! $loop->first ? 'mt-2 border-t border-current/10 pt-2' : '' }} whitespace-pre-line">{{ $usage['purpose'] }}</p>
                            @endforeach
                        </td>
                        <td class="px-4 py-4 align-top"></td>
                        <td class="px-4 py-4 align-top"></td>
                        <td class="px-4 py-4 text-right align-top font-semibold">{{ $hasPerjadin ? 'Rp '.$money($realization) : '' }}</td>
                        <td class="px-4 py-4 text-right align-top">{{ $hasPerjadin ? $percent($percentage) : '' }}</td>
                        <td class="px-4 py-4 text-right align-top">{{ $hasPerjadin ? $percent($percentage) : '' }}</td>
                        <td class="max-w-sm px-4 py-4 align-top">
                            @foreach ($linkedUsages as $usage)
                                <p class="{{ ! $loop->first ? 'mt-2 border-t border-current/10 pt-2' : '' }}">{{ $usage['location'] }}</p>
                            @endforeach
                        </td>
                        <td class="px-4 py-4 align-top"></td>
                        <td class="px-4 py-4 align-top"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
