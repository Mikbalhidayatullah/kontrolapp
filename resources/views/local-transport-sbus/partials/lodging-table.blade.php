@php
    $typeKey = $typeKey ?? 'lodging_regional';
@endphp

<table class="min-w-full divide-y divide-slate-200 text-sm">
    <thead class="sticky top-0 z-10 bg-slate-950 text-left text-slate-200">
        <tr>
            <th class="px-4 py-3 font-medium">Wilayah</th>
            <th class="px-4 py-3 font-medium">Satuan</th>
            <th class="px-4 py-3 font-medium">Kepala Daerah / Eselon I</th>
            <th class="px-4 py-3 font-medium">Anggota DPRD / Eselon II</th>
            <th class="px-4 py-3 font-medium">Eselon III / Gol. IV</th>
            <th class="px-4 py-3 font-medium">Eselon IV / Gol. III, II, I</th>
            <th class="px-4 py-3 font-medium">Status</th>
            @if (auth()->user()->hasAnyRole(['admin', 'bendahara']))
                <th class="px-4 py-3 font-medium">Aksi</th>
            @endif
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-100 bg-white">
        @foreach ($items as $entry)
            <tr>
                <td class="px-4 py-4 align-top font-semibold text-slate-900">{{ $entry->region_name ?? $entry->province_name }}</td>
                <td class="px-4 py-4 align-top text-slate-600">{{ $entry->unit_label }}</td>
                <td class="px-4 py-4 align-top text-slate-600">Rp {{ number_format($entry->head_region_amount, 0, ',', '.') }}</td>
                <td class="px-4 py-4 align-top text-slate-600">Rp {{ number_format($entry->member_eselon_2_amount, 0, ',', '.') }}</td>
                <td class="px-4 py-4 align-top text-slate-600">Rp {{ number_format($entry->eselon_3_gol_4_amount, 0, ',', '.') }}</td>
                <td class="px-4 py-4 align-top font-semibold text-slate-900">Rp {{ number_format($entry->eselon_4_gol_3_2_1_amount, 0, ',', '.') }}</td>
                <td class="px-4 py-4 align-top">
                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $entry->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $entry->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </td>
                @if (auth()->user()->hasAnyRole(['admin', 'bendahara']))
                    <td class="px-4 py-4 align-top">
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('local-transport-sbus.entries.edit', ['type' => $typeKey, 'id' => $entry->id]) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100" title="Edit data" aria-label="Edit data">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true">
                                    <path d="M4 20h4l10.5-10.5a2.121 2.121 0 0 0-3-3L5 17v3Z" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="m13.5 6.5 3 3" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </a>
                            <form action="{{ route('local-transport-sbus.entries.destroy', ['type' => $typeKey, 'id' => $entry->id]) }}" method="POST" onsubmit="return confirm('Hapus data SBU ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100" title="Hapus data" aria-label="Hapus data">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true">
                                        <path d="M3 6h18" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M19 6l-1 14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1L5 6" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M10 11v6M14 11v6" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                @endif
            </tr>
        @endforeach
    </tbody>
</table>
