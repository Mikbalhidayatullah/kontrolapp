<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    <div class="space-y-6">
        <section class="grid gap-4 lg:grid-cols-3">
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Kategori SBU</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $summary['sectionCount'] }}</p>
                <p class="mt-2 text-sm text-slate-500">Master dipisah sesuai kegunaan masing-masing</p>
            </article>
            <article class="rounded-3xl border border-emerald-200 bg-emerald-50/60 p-5 shadow-sm">
                <p class="text-sm font-medium text-emerald-700">Total Data Master</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $summary['totalCount'] }}</p>
                <p class="mt-2 text-sm text-slate-500">Seluruh data acuan dari semua kelompok SBU</p>
            </article>
            <article class="rounded-3xl border border-sky-200 bg-sky-50/60 p-5 shadow-sm">
                <p class="text-sm font-medium text-sky-700">Data Aktif</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $summary['activeCount'] }}</p>
                <p class="mt-2 text-sm text-slate-500">Data aktif yang siap dipakai sebagai acuan</p>
            </article>
        </section>

        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-sky-600">Master SBU</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Daftar acuan dipisah sesuai kegunaan</h2>
                    <p class="mt-2 text-sm text-slate-500">SBU dibagi antara transport lokal, taksi bandara, tiket pesawat, penginapan, uang representasi, dan uang harian.</p>
                </div>
                @if (auth()->user()->hasAnyRole(['admin', 'bendahara']))
                    <a href="{{ route('local-transport-sbus.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700">
                        Tambah SBU
                    </a>
                @endif
            </div>
        </section>

        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm" data-sbu-tabs>
            <div class="flex flex-col gap-4 border-b border-slate-200 pb-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-amber-600">Kelompok Master</p>
                        <h3 class="mt-1 text-xl font-semibold text-slate-900">Pilih jenis SBU yang ingin dilihat</h3>
                    </div>
                    <span class="rounded-full border border-sky-200 bg-slate-50 px-4 py-2 text-sm text-slate-600">{{ count($sections) }} jenis SBU</span>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach ($sections as $index => $section)
                        <button
                            type="button"
                            data-sbu-tab="{{ $index }}"
                            class="inline-flex items-center justify-center rounded-2xl border px-4 py-2.5 text-sm font-semibold transition {{ $index === 0 ? 'border-slate-900 bg-slate-900 text-white shadow-sm' : 'border-slate-900 bg-white text-slate-900 hover:bg-slate-50 shadow-sm' }}"
                        >
                            {{ $section['label'] }}
                            <span class="ml-2 rounded-full {{ $index === 0 ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }} px-2 py-0.5 text-xs">
                                {{ $section['count'] }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="mt-5 space-y-5">
                @foreach ($sections as $index => $section)
                    <div data-sbu-panel="{{ $index }}" class="{{ $index === 0 ? '' : 'hidden' }}">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <h4 class="text-lg font-semibold text-slate-900">{{ $section['label'] }}</h4>
                                <p class="mt-1 text-sm text-slate-500">{{ $section['description'] }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2 text-sm">
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-slate-600">{{ $section['count'] }} data</span>
                                <span class="rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-700">{{ $section['activeCount'] }} aktif</span>
                            </div>
                        </div>

                        @if (!empty($section['variants']))
                            <div class="mt-5 rounded-3xl border border-slate-200 p-4" data-sbu-subtabs>
                                <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-4">
                                    @foreach ($section['variants'] as $variantIndex => $variant)
                                        <button
                                            type="button"
                                            data-sbu-subtab="{{ $section['key'] }}-{{ $variantIndex }}"
                                            class="rounded-full border px-4 py-2 text-sm font-medium transition {{ $variantIndex === 0 ? 'border-slate-900 bg-slate-900 text-white shadow-sm' : 'border-slate-900 bg-white text-slate-900 hover:bg-slate-50 shadow-sm' }}"
                                        >
                                            {{ $variant['label'] }}
                                            <span class="ml-2 rounded-full {{ $variantIndex === 0 ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }} px-2 py-0.5 text-xs">
                                                {{ $variant['count'] }}
                                            </span>
                                        </button>
                                    @endforeach
                                </div>

                                <div class="mt-4 space-y-4">
                                    @foreach ($section['variants'] as $variantIndex => $variant)
                                        <div data-sbu-subpanel="{{ $section['key'] }}-{{ $variantIndex }}" class="{{ $variantIndex === 0 ? '' : 'hidden' }}">
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                                                <div>
                                                    <h5 class="text-base font-semibold text-slate-900">{{ $variant['label'] }}</h5>
                                                    <p class="mt-1 text-sm text-slate-500">{{ $variant['description'] }}</p>
                                                </div>
                                                <div class="flex flex-wrap gap-2 text-sm">
                                                    <span class="rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-slate-600">{{ $variant['count'] }} data</span>
                                                    <span class="rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-700">{{ $variant['activeCount'] }} aktif</span>
                                                </div>
                                            </div>

                                            @if ($variant['count'] === 0)
                                                <div class="mt-4 rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                                                    Belum ada data master untuk kelompok ini.
                                                </div>
                                            @else
                                                <div class="mt-4 overflow-hidden rounded-3xl border border-slate-200">
                                                    <div class="{{ $variant['count'] > 10 ? 'max-h-[640px] overflow-y-auto' : '' }}">
                                                        <div class="overflow-x-auto">
                                                            @if ($variant['table'] === 'transport')
                                                                @include('local-transport-sbus.partials.transport-table', ['items' => $variant['items'], 'typeKey' => $variant['key'] === 'transport_taxi' ? 'transport_taxi' : 'transport_local'])
                                                            @elseif ($variant['table'] === 'lodging')
                                                                @include('local-transport-sbus.partials.lodging-table', ['items' => $variant['items'], 'typeKey' => str_contains($variant['key'], 'national') ? 'lodging_national' : 'lodging_regional'])
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @elseif ($section['count'] === 0)
                            <div class="mt-5 rounded-3xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                                Belum ada data master untuk kelompok ini.
                            </div>
                        @else
                            <div class="mt-5 overflow-hidden rounded-3xl border border-slate-200">
                                <div class="{{ $section['count'] > 10 ? 'max-h-[640px] overflow-y-auto' : '' }}">
                                        <div class="overflow-x-auto">
                                            @switch($section['table'])
                                                @case('transport')
                                                    @include('local-transport-sbus.partials.transport-table', ['items' => $section['items'], 'typeKey' => $section['key'] === 'transport_taxi' ? 'transport_taxi' : 'transport_local'])
                                                @break

                                                @case('flight_ticket')
                                                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                                                        <thead class="sticky top-0 z-10 bg-slate-950 text-left text-slate-200">
                                                        <tr>
                                                            <th class="px-4 py-3 font-medium">Asal</th>
                                                            <th class="px-4 py-3 font-medium">Tujuan</th>
                                                            <th class="px-4 py-3 font-medium">Bisnis</th>
                                                            <th class="px-4 py-3 font-medium">Ekonomi</th>
                                                            <th class="px-4 py-3 font-medium">Status</th>
                                                            @if (auth()->user()->hasAnyRole(['admin', 'bendahara']))
                                                                <th class="px-4 py-3 font-medium">Aksi</th>
                                                            @endif
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100 bg-white">
                                                        @foreach ($section['items'] as $entry)
                                                            <tr>
                                                                <td class="px-4 py-4 align-top font-semibold text-slate-900">{{ $entry->origin_city }}</td>
                                                                <td class="px-4 py-4 align-top font-semibold text-slate-900">{{ $entry->destination_city }}</td>
                                                                <td class="px-4 py-4 align-top text-slate-600">{{ $entry->business_amount > 0 ? 'Rp '.number_format($entry->business_amount, 0, ',', '.') : '-' }}</td>
                                                                <td class="px-4 py-4 align-top font-semibold text-slate-900">{{ $entry->economy_amount > 0 ? 'Rp '.number_format($entry->economy_amount, 0, ',', '.') : '-' }}</td>
                                                                <td class="px-4 py-4 align-top">
                                                                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $entry->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $entry->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                                                </td>
                                                                @if (auth()->user()->hasAnyRole(['admin', 'bendahara']))
                                                                    <td class="px-4 py-4 align-top">
                                                                        <div class="flex flex-wrap gap-2">
                                                                            <a href="{{ route('local-transport-sbus.entries.edit', ['type' => 'flight_ticket', 'id' => $entry->id]) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100" title="Edit data" aria-label="Edit data">
                                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true"><path d="M4 20h4l10.5-10.5a2.121 2.121 0 0 0-3-3L5 17v3Z" stroke-linecap="round" stroke-linejoin="round" /><path d="m13.5 6.5 3 3" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                                                            </a>
                                                                            <form action="{{ route('local-transport-sbus.entries.destroy', ['type' => 'flight_ticket', 'id' => $entry->id]) }}" method="POST" onsubmit="return confirm('Hapus data SBU ini?');">
                                                                                @csrf
                                                                                @method('DELETE')
                                                                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100" title="Hapus data" aria-label="Hapus data">
                                                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true"><path d="M3 6h18" stroke-linecap="round" stroke-linejoin="round" /><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" stroke-linecap="round" stroke-linejoin="round" /><path d="M19 6l-1 14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1L5 6" stroke-linecap="round" stroke-linejoin="round" /><path d="M10 11v6M14 11v6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                                                                </button>
                                                                            </form>
                                                                        </div>
                                                                    </td>
                                                                @endif
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                                @break

                                            @case('representation')
                                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                                    <thead class="sticky top-0 z-10 bg-slate-950 text-left text-slate-200">
                                                        <tr>
                                                            <th class="px-4 py-3 font-medium">Kelompok Jabatan</th>
                                                            <th class="px-4 py-3 font-medium">Satuan</th>
                                                            <th class="px-4 py-3 font-medium">Luar Kota</th>
                                                            <th class="px-4 py-3 font-medium">Dalam Kota > 8 Jam</th>
                                                            <th class="px-4 py-3 font-medium">Status</th>
                                                            @if (auth()->user()->hasAnyRole(['admin', 'bendahara']))
                                                                <th class="px-4 py-3 font-medium">Aksi</th>
                                                            @endif
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100 bg-white">
                                                        @foreach ($section['items'] as $entry)
                                                            <tr>
                                                                <td class="px-4 py-4 align-top font-semibold text-slate-900">{{ $entry->position_group }}</td>
                                                                <td class="px-4 py-4 align-top text-slate-600">{{ $entry->unit_label }}</td>
                                                                <td class="px-4 py-4 align-top text-slate-600">Rp {{ number_format($entry->outside_city_amount, 0, ',', '.') }}</td>
                                                                <td class="px-4 py-4 align-top font-semibold text-slate-900">Rp {{ number_format($entry->inside_city_over_8_hours_amount, 0, ',', '.') }}</td>
                                                                <td class="px-4 py-4 align-top">
                                                                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $entry->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $entry->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                                                </td>
                                                                @if (auth()->user()->hasAnyRole(['admin', 'bendahara']))
                                                                    <td class="px-4 py-4 align-top">
                                                                        <div class="flex flex-wrap gap-2">
                                                                            <a href="{{ route('local-transport-sbus.entries.edit', ['type' => 'representation', 'id' => $entry->id]) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100" title="Edit data" aria-label="Edit data">
                                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true"><path d="M4 20h4l10.5-10.5a2.121 2.121 0 0 0-3-3L5 17v3Z" stroke-linecap="round" stroke-linejoin="round" /><path d="m13.5 6.5 3 3" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                                                            </a>
                                                                            <form action="{{ route('local-transport-sbus.entries.destroy', ['type' => 'representation', 'id' => $entry->id]) }}" method="POST" onsubmit="return confirm('Hapus data SBU ini?');">
                                                                                @csrf
                                                                                @method('DELETE')
                                                                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100" title="Hapus data" aria-label="Hapus data">
                                                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true"><path d="M3 6h18" stroke-linecap="round" stroke-linejoin="round" /><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" stroke-linecap="round" stroke-linejoin="round" /><path d="M19 6l-1 14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1L5 6" stroke-linecap="round" stroke-linejoin="round" /><path d="M10 11v6M14 11v6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                                                                </button>
                                                                            </form>
                                                                        </div>
                                                                    </td>
                                                                @endif
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                                @break

                                            @case('daily_allowance')
                                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                                    <thead class="sticky top-0 z-10 bg-slate-950 text-left text-slate-200">
                                                        <tr>
                                                            <th class="px-4 py-3 font-medium">Provinsi</th>
                                                            <th class="px-4 py-3 font-medium">Satuan</th>
                                                            <th class="px-4 py-3 font-medium">Luar Kota</th>
                                                            <th class="px-4 py-3 font-medium">Dalam Kota Sofifi > 8 Jam</th>
                                                            <th class="px-4 py-3 font-medium">Diklat</th>
                                                            <th class="px-4 py-3 font-medium">Status</th>
                                                            @if (auth()->user()->hasAnyRole(['admin', 'bendahara']))
                                                                <th class="px-4 py-3 font-medium">Aksi</th>
                                                            @endif
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100 bg-white">
                                                        @foreach ($section['items'] as $entry)
                                                            <tr>
                                                                <td class="px-4 py-4 align-top font-semibold text-slate-900">{{ $entry->province_name }}</td>
                                                                <td class="px-4 py-4 align-top text-slate-600">{{ $entry->unit_label }}</td>
                                                                <td class="px-4 py-4 align-top text-slate-600">Rp {{ number_format($entry->outside_city_amount, 0, ',', '.') }}</td>
                                                                <td class="px-4 py-4 align-top text-slate-600">{{ $entry->sofifi_inside_city_over_8_hours_amount > 0 ? 'Rp '.number_format($entry->sofifi_inside_city_over_8_hours_amount, 0, ',', '.') : '-' }}</td>
                                                                <td class="px-4 py-4 align-top font-semibold text-slate-900">Rp {{ number_format($entry->diklat_amount, 0, ',', '.') }}</td>
                                                                <td class="px-4 py-4 align-top">
                                                                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $entry->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $entry->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                                                </td>
                                                                @if (auth()->user()->hasAnyRole(['admin', 'bendahara']))
                                                                    <td class="px-4 py-4 align-top">
                                                                        <div class="flex flex-wrap gap-2">
                                                                            <a href="{{ route('local-transport-sbus.entries.edit', ['type' => 'daily_allowance', 'id' => $entry->id]) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100" title="Edit data" aria-label="Edit data">
                                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true"><path d="M4 20h4l10.5-10.5a2.121 2.121 0 0 0-3-3L5 17v3Z" stroke-linecap="round" stroke-linejoin="round" /><path d="m13.5 6.5 3 3" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                                                            </a>
                                                                            <form action="{{ route('local-transport-sbus.entries.destroy', ['type' => 'daily_allowance', 'id' => $entry->id]) }}" method="POST" onsubmit="return confirm('Hapus data SBU ini?');">
                                                                                @csrf
                                                                                @method('DELETE')
                                                                                <button type="submit" class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-rose-200 bg-rose-50 text-rose-700 transition hover:bg-rose-100" title="Hapus data" aria-label="Hapus data">
                                                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4" aria-hidden="true"><path d="M3 6h18" stroke-linecap="round" stroke-linejoin="round" /><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" stroke-linecap="round" stroke-linejoin="round" /><path d="M19 6l-1 14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1L5 6" stroke-linecap="round" stroke-linejoin="round" /><path d="M10 11v6M14 11v6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                                                                </button>
                                                                            </form>
                                                                        </div>
                                                                    </td>
                                                                @endif
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                                @break
                                        @endswitch
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const wrapper = document.querySelector('[data-sbu-tabs]');
            if (!wrapper) return;

            const activateButtonState = (buttons, target, datasetKey, activeClasses, idleClasses) => {
                buttons.forEach((button) => {
                    const isActive = button.dataset[datasetKey] === target;
                    activeClasses.forEach((className) => button.classList.toggle(className, isActive));
                    idleClasses.forEach((className) => button.classList.toggle(className, !isActive));

                    const badge = button.querySelector('span');
                    if (badge) {
                        badge.classList.toggle('bg-white/20', isActive);
                        badge.classList.toggle('text-white', isActive);
                        badge.classList.toggle('bg-white/20', !isActive);
                        badge.classList.toggle('text-white', !isActive);
                    }
                });
            };

            const mainButtons = Array.from(wrapper.querySelectorAll('[data-sbu-tab]'));
            const mainPanels = Array.from(wrapper.querySelectorAll('[data-sbu-panel]'));

            const activateMainTab = (target) => {
                activateButtonState(
                    mainButtons,
                    target,
                    'sbuTab',
                    ['border-slate-900', 'bg-slate-900', 'text-white', 'shadow-sm'],
                    ['border-slate-900', 'bg-white', 'text-slate-900', 'hover:bg-slate-50', 'shadow-sm']
                );

                mainPanels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.dataset.sbuPanel !== target);
                });
            };

            mainButtons.forEach((button) => {
                button.addEventListener('click', () => activateMainTab(button.dataset.sbuTab));
            });

            const subtabWrappers = Array.from(wrapper.querySelectorAll('[data-sbu-subtabs]'));
            subtabWrappers.forEach((subWrapper) => {
                const subButtons = Array.from(subWrapper.querySelectorAll('[data-sbu-subtab]'));
                const subPanels = Array.from(subWrapper.querySelectorAll('[data-sbu-subpanel]'));

                const activateSubTab = (target) => {
                    activateButtonState(
                        subButtons,
                        target,
                        'sbuSubtab',
                        ['border-slate-900', 'bg-slate-900', 'text-white', 'shadow-sm'],
                        ['border-slate-900', 'bg-white', 'text-slate-900', 'hover:bg-slate-50', 'shadow-sm']
                    );

                    subPanels.forEach((panel) => {
                        panel.classList.toggle('hidden', panel.dataset.sbuSubpanel !== target);
                    });
                };

                subButtons.forEach((button) => {
                    button.addEventListener('click', () => activateSubTab(button.dataset.sbuSubtab));
                });
            });
        });
    </script>
</x-layout>
