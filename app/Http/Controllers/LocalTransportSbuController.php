<?php

namespace App\Http\Controllers;

use App\Models\DailyAllowanceSbu;
use App\Models\FlightTicketSbu;
use App\Models\LocalTransportSbu;
use App\Models\LodgingSbu;
use App\Models\NationalLodgingSbu;
use App\Models\RepresentationSbu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class LocalTransportSbuController extends Controller
{
    public function index(): View
    {
        $transportLocalEntries = LocalTransportSbu::query()
            ->where(function ($query) {
                $query->whereNull('row_code')
                    ->orWhere('row_code', 'not like', 'TX-%');
            })
            ->orderBy('sort_order')
            ->orderBy('area_name')
            ->orderBy('row_code')
            ->orderBy('route_name')
            ->get();

        $airportTaxiEntries = LocalTransportSbu::query()
            ->where('row_code', 'like', 'TX-%')
            ->orderBy('sort_order')
            ->orderBy('row_code')
            ->get();

        $flightTicketEntries = FlightTicketSbu::query()
            ->orderBy('sort_order')
            ->orderBy('origin_city')
            ->orderBy('destination_city')
            ->get();

        $regionalLodgingEntries = LodgingSbu::query()
            ->orderBy('sort_order')
            ->orderBy('region_name')
            ->get();

        $nationalLodgingEntries = NationalLodgingSbu::query()
            ->orderBy('sort_order')
            ->orderBy('province_name')
            ->get();

        $representationEntries = RepresentationSbu::query()
            ->orderBy('sort_order')
            ->orderBy('position_group')
            ->get();

        $dailyAllowanceEntries = DailyAllowanceSbu::query()
            ->orderBy('sort_order')
            ->orderBy('province_name')
            ->get();

        $transportLocalVariants = $this->groupTransportLocalByArea($transportLocalEntries);

        $sections = [
            [
                'key' => 'transport_local',
                'label' => 'Transport Lokal',
                'description' => 'Acuan transport lokal dalam daerah. Data dipisah lagi per kabupaten/kota agar lebih cepat dicari.',
                'count' => $transportLocalEntries->count(),
                'activeCount' => $transportLocalEntries->where('is_active', true)->count(),
                'variants' => $transportLocalVariants,
            ],
            [
                'key' => 'transport_taxi',
                'label' => 'Taksi Bandara',
                'description' => 'Master acuan biaya maksimal transport taksi bandara untuk perjadin luar daerah.',
                'count' => $airportTaxiEntries->count(),
                'activeCount' => $airportTaxiEntries->where('is_active', true)->count(),
                'items' => $airportTaxiEntries,
                'table' => 'transport',
            ],
            [
                'key' => 'flight_ticket',
                'label' => 'Tiket Pesawat',
                'description' => 'Acuan tiket pesawat perjalanan dinas luar daerah pergi pulang (PP).',
                'count' => $flightTicketEntries->count(),
                'activeCount' => $flightTicketEntries->where('is_active', true)->count(),
                'items' => $flightTicketEntries,
                'table' => 'flight_ticket',
            ],
            [
                'key' => 'lodging',
                'label' => 'Penginapan',
                'description' => 'Acuan penginapan dipisah antara dalam daerah Maluku Utara dan luar daerah Indonesia.',
                'count' => $regionalLodgingEntries->count() + $nationalLodgingEntries->count(),
                'activeCount' => $regionalLodgingEntries->where('is_active', true)->count() + $nationalLodgingEntries->where('is_active', true)->count(),
                'variants' => [
                    [
                        'key' => 'lodging_regional',
                        'label' => 'Dalam Daerah',
                        'description' => 'Acuan penginapan untuk perjalanan dinas dalam daerah Provinsi Maluku Utara.',
                        'count' => $regionalLodgingEntries->count(),
                        'activeCount' => $regionalLodgingEntries->where('is_active', true)->count(),
                        'items' => $regionalLodgingEntries,
                        'table' => 'lodging',
                    ],
                    [
                        'key' => 'lodging_national',
                        'label' => 'Luar Daerah',
                        'description' => 'Acuan penginapan untuk perjalanan dinas luar daerah di Indonesia.',
                        'count' => $nationalLodgingEntries->count(),
                        'activeCount' => $nationalLodgingEntries->where('is_active', true)->count(),
                        'items' => $nationalLodgingEntries,
                        'table' => 'lodging',
                    ],
                ],
            ],
            [
                'key' => 'representation',
                'label' => 'Uang Representasi',
                'description' => 'Acuan uang representasi perjalanan dinas dalam negeri untuk dalam dan luar daerah.',
                'count' => $representationEntries->count(),
                'activeCount' => $representationEntries->where('is_active', true)->count(),
                'items' => $representationEntries,
                'table' => 'representation',
            ],
            [
                'key' => 'daily_allowance',
                'label' => 'Uang Harian',
                'description' => 'Acuan uang harian perjalanan dinas dalam negeri, termasuk Sofifi lebih dari 8 jam.',
                'count' => $dailyAllowanceEntries->count(),
                'activeCount' => $dailyAllowanceEntries->where('is_active', true)->count(),
                'items' => $dailyAllowanceEntries,
                'table' => 'daily_allowance',
            ],
        ];

        return view('local-transport-sbus.index', [
            'title' => 'SBU',
            'sections' => $sections,
            'summary' => [
                'sectionCount' => count($sections),
                'totalCount' => collect($sections)->sum('count'),
                'activeCount' => collect($sections)->sum('activeCount'),
            ],
        ]);
    }

    public function create(): View
    {
        return $this->formView('Tambah SBU');
    }

    public function store(Request $request): RedirectResponse
    {
        $type = (string) $request->input('sbu_type', 'transport_local');
        $label = $this->storeByType($request, $type);

        return redirect()->route('local-transport-sbus.index')
            ->with('status', 'Data '.$label.' berhasil ditambahkan.');
    }

    public function edit(LocalTransportSbu $localTransportSbu): View
    {
        return $this->formView('Edit SBU Transport Lokal', $localTransportSbu);
    }

    public function update(Request $request, LocalTransportSbu $localTransportSbu): RedirectResponse
    {
        $localTransportSbu->update($this->validatedLocalTransportData($request));

        return redirect()->route('local-transport-sbus.index')
            ->with('status', 'Data SBU transport lokal berhasil diperbarui.');
    }

    public function destroy(LocalTransportSbu $localTransportSbu): RedirectResponse
    {
        $localTransportSbu->delete();

        return redirect()->route('local-transport-sbus.index')
            ->with('status', 'Data SBU transport lokal berhasil dihapus.');
    }

    public function editEntry(string $type, int $id): View
    {
        ['entry' => $entry, 'label' => $label, 'type' => $resolvedType] = $this->resolveEntry($type, $id);

        return $this->formView('Edit '.$label, $entry, $resolvedType);
    }

    public function updateEntry(Request $request, string $type, int $id): RedirectResponse
    {
        ['entry' => $entry, 'label' => $label, 'type' => $resolvedType] = $this->resolveEntry($type, $id);

        $this->updateByType($request, $resolvedType, $entry);

        return redirect()->route('local-transport-sbus.index')
            ->with('status', 'Data '.$label.' berhasil diperbarui.');
    }

    public function destroyEntry(string $type, int $id): RedirectResponse
    {
        ['entry' => $entry, 'label' => $label] = $this->resolveEntry($type, $id);

        $entry->delete();

        return redirect()->route('local-transport-sbus.index')
            ->with('status', 'Data '.$label.' berhasil dihapus.');
    }

    private function formView(string $title, mixed $entry = null, ?string $forcedType = null): View
    {
        return view('local-transport-sbus.form', [
            'title' => $title,
            'entry' => $entry,
            'sbuTypes' => $this->sbuTypeOptions(),
            'forcedType' => $forcedType,
        ]);
    }

    private function storeByType(Request $request, string $type): string
    {
        return match ($type) {
            'transport_local' => $this->storeTransportLocal($request),
            'transport_taxi' => $this->storeAirportTaxi($request),
            'flight_ticket' => $this->storeFlightTicket($request),
            'lodging_regional' => $this->storeRegionalLodging($request),
            'lodging_national' => $this->storeNationalLodging($request),
            'representation' => $this->storeRepresentation($request),
            'daily_allowance' => $this->storeDailyAllowance($request),
            default => abort(422, 'Jenis SBU tidak dikenali.'),
        };
    }

    private function updateByType(Request $request, string $type, mixed $entry): void
    {
        match ($type) {
            'transport_local' => $entry->update($this->validatedLocalTransportData($request)),
            'transport_taxi' => $entry->update($this->validatedAirportTaxiData($request)),
            'flight_ticket' => $entry->update($this->validatedFlightTicketData($request)),
            'lodging_regional' => $entry->update($this->validatedRegionalLodgingData($request)),
            'lodging_national' => $entry->update($this->validatedNationalLodgingData($request)),
            'representation' => $entry->update($this->validatedRepresentationData($request)),
            'daily_allowance' => $entry->update($this->validatedDailyAllowanceData($request)),
            default => abort(422, 'Jenis SBU tidak dikenali.'),
        };
    }

    private function storeTransportLocal(Request $request): string
    {
        LocalTransportSbu::create($this->validatedLocalTransportData($request));
        return 'SBU transport lokal';
    }

    private function storeAirportTaxi(Request $request): string
    {
        LocalTransportSbu::create($this->validatedAirportTaxiData($request));

        return 'SBU taksi bandara';
    }

    private function storeFlightTicket(Request $request): string
    {
        FlightTicketSbu::create($this->validatedFlightTicketData($request));

        return 'SBU tiket pesawat';
    }

    private function storeRegionalLodging(Request $request): string
    {
        LodgingSbu::create($this->validatedRegionalLodgingData($request));

        return 'SBU penginapan dalam daerah';
    }

    private function storeNationalLodging(Request $request): string
    {
        NationalLodgingSbu::create($this->validatedNationalLodgingData($request));

        return 'SBU penginapan luar daerah';
    }

    private function storeRepresentation(Request $request): string
    {
        RepresentationSbu::create($this->validatedRepresentationData($request));

        return 'SBU uang representasi';
    }

    private function storeDailyAllowance(Request $request): string
    {
        DailyAllowanceSbu::create($this->validatedDailyAllowanceData($request));

        return 'SBU uang harian';
    }

    private function validatedAirportTaxiData(Request $request): array
    {
        $data = $request->validate([
            'row_code' => ['nullable', 'string', 'max:20'],
            'origin_label' => ['required', 'string', 'max:255'],
            'destination_label' => ['required', 'string', 'max:255'],
            'unit_label' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        return [
            'component_key' => 'transport_taxi_airport',
            'area_name' => 'TRANSPORTASI TAKSI BANDARA',
            'row_code' => $data['row_code'] ?? null,
            'origin_regency' => 'Bandara',
            'origin_label' => $data['origin_label'],
            'destination_regency' => 'Luar Daerah',
            'destination_label' => $data['destination_label'],
            'route_name' => trim(($data['origin_label'] ?? '').' -> '.($data['destination_label'] ?? '')),
            'unit_label' => $data['unit_label'],
            'amount' => $this->moneyToInt($data['amount']),
            'notes' => $data['notes'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function validatedFlightTicketData(Request $request): array
    {
        $data = $request->validate([
            'origin_city' => ['required', 'string', 'max:255'],
            'destination_city' => ['required', 'string', 'max:255'],
            'business_amount' => ['nullable', 'string'],
            'economy_amount' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        return [
            'origin_city' => strtoupper(trim($data['origin_city'])),
            'destination_city' => strtoupper(trim($data['destination_city'])),
            'business_amount' => $this->moneyToInt($data['business_amount'] ?? null),
            'economy_amount' => $this->moneyToInt($data['economy_amount'] ?? null),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function validatedRegionalLodgingData(Request $request): array
    {
        $data = $request->validate([
            'region_name' => ['required', 'string', 'max:255'],
            'unit_label' => ['required', 'string', 'max:50'],
            'head_region_amount' => ['required', 'string'],
            'member_eselon_2_amount' => ['required', 'string'],
            'eselon_3_gol_4_amount' => ['required', 'string'],
            'eselon_4_gol_3_2_1_amount' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        return [
            'region_name' => strtoupper(trim($data['region_name'])),
            'unit_label' => $data['unit_label'],
            'head_region_amount' => $this->moneyToInt($data['head_region_amount']),
            'member_eselon_2_amount' => $this->moneyToInt($data['member_eselon_2_amount']),
            'eselon_3_gol_4_amount' => $this->moneyToInt($data['eselon_3_gol_4_amount']),
            'eselon_4_gol_3_2_1_amount' => $this->moneyToInt($data['eselon_4_gol_3_2_1_amount']),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function validatedNationalLodgingData(Request $request): array
    {
        $data = $request->validate([
            'province_name' => ['required', 'string', 'max:255'],
            'unit_label' => ['required', 'string', 'max:50'],
            'head_region_amount' => ['required', 'string'],
            'member_eselon_2_amount' => ['required', 'string'],
            'eselon_3_gol_4_amount' => ['required', 'string'],
            'eselon_4_gol_3_2_1_amount' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        return [
            'province_name' => strtoupper(trim($data['province_name'])),
            'unit_label' => $data['unit_label'],
            'head_region_amount' => $this->moneyToInt($data['head_region_amount']),
            'member_eselon_2_amount' => $this->moneyToInt($data['member_eselon_2_amount']),
            'eselon_3_gol_4_amount' => $this->moneyToInt($data['eselon_3_gol_4_amount']),
            'eselon_4_gol_3_2_1_amount' => $this->moneyToInt($data['eselon_4_gol_3_2_1_amount']),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function validatedRepresentationData(Request $request): array
    {
        $data = $request->validate([
            'position_group' => ['required', 'string', 'max:255'],
            'unit_label' => ['required', 'string', 'max:50'],
            'outside_city_amount' => ['required', 'string'],
            'inside_city_over_8_hours_amount' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        return [
            'position_group' => strtoupper(trim($data['position_group'])),
            'unit_label' => $data['unit_label'],
            'outside_city_amount' => $this->moneyToInt($data['outside_city_amount']),
            'inside_city_over_8_hours_amount' => $this->moneyToInt($data['inside_city_over_8_hours_amount']),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function validatedDailyAllowanceData(Request $request): array
    {
        $data = $request->validate([
            'province_name' => ['required', 'string', 'max:255'],
            'unit_label' => ['required', 'string', 'max:50'],
            'outside_city_amount' => ['required', 'string'],
            'sofifi_inside_city_over_8_hours_amount' => ['nullable', 'string'],
            'diklat_amount' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        return [
            'province_name' => strtoupper(trim($data['province_name'])),
            'unit_label' => $data['unit_label'],
            'outside_city_amount' => $this->moneyToInt($data['outside_city_amount']),
            'sofifi_inside_city_over_8_hours_amount' => $this->moneyToInt($data['sofifi_inside_city_over_8_hours_amount'] ?? null),
            'diklat_amount' => $this->moneyToInt($data['diklat_amount']),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function groupTransportLocalByArea(Collection $entries): array
    {
        return $entries
            ->groupBy(fn (LocalTransportSbu $entry) => $entry->area_name ?: 'Lainnya')
            ->map(function (Collection $items, string $area): array {
                return [
                    'key' => 'transport_area_'.md5($area),
                    'label' => $area,
                    'buttonLabel' => $this->shortAreaLabel($area),
                    'sortLabel' => $this->sortAreaLabel($area),
                    'description' => 'Acuan rute transport lokal untuk wilayah '.$area.'.',
                    'count' => $items->count(),
                    'activeCount' => $items->where('is_active', true)->count(),
                    'items' => $items->values(),
                    'table' => 'transport',
                ];
            })
            ->sortBy('sortLabel')
            ->values()
            ->map(function (array $variant): array {
                unset($variant['sortLabel']);

                return $variant;
            })
            ->all();
    }

    private function shortAreaLabel(string $area): string
    {
        $short = preg_replace('/^(KABUPATEN|KOTA)\s+/i', '', trim($area));
        return $short !== '' ? $short : $area;
    }

    private function sortAreaLabel(string $area): string
    {
        return strtoupper($this->shortAreaLabel($area));
    }

    private function moneyToInt(?string $value): int
    {
        if ($value === null) {
            return 0;
        }

        return (int) preg_replace('/\D/', '', $value);
    }

    private function validatedLocalTransportData(Request $request): array
    {
        $data = $request->validate([
            'area_name' => ['required', 'string', 'max:255'],
            'row_code' => ['nullable', 'string', 'max:20'],
            'origin_regency' => ['nullable', 'string', 'max:255'],
            'origin_label' => ['required', 'string', 'max:255'],
            'destination_regency' => ['required', 'string', 'max:255'],
            'destination_label' => ['required', 'string', 'max:255'],
            'unit_label' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        return [
            'component_key' => 'local_transport_other',
            'area_name' => $data['area_name'],
            'row_code' => $data['row_code'] ?? null,
            'origin_regency' => $data['origin_regency'] ?? null,
            'origin_label' => $data['origin_label'],
            'destination_regency' => $data['destination_regency'],
            'destination_label' => $data['destination_label'],
            'route_name' => trim(($data['origin_label'] ?? '').' -> '.($data['destination_label'] ?? '')),
            'unit_label' => $data['unit_label'],
            'amount' => $this->moneyToInt($data['amount']),
            'notes' => $data['notes'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];
    }

    private function sbuTypeOptions(): array
    {
        return [
            'transport_local' => 'Transport Lokal',
            'transport_taxi' => 'Taksi Bandara',
            'flight_ticket' => 'Tiket Pesawat',
            'lodging_regional' => 'Penginapan Dalam Daerah',
            'lodging_national' => 'Penginapan Luar Daerah',
            'representation' => 'Uang Representasi',
            'daily_allowance' => 'Uang Harian',
        ];
    }

    private function resolveEntry(string $type, int $id): array
    {
        return match ($type) {
            'transport_local' => ['entry' => LocalTransportSbu::findOrFail($id), 'label' => 'SBU transport lokal', 'type' => $type],
            'transport_taxi' => ['entry' => LocalTransportSbu::findOrFail($id), 'label' => 'SBU taksi bandara', 'type' => $type],
            'flight_ticket' => ['entry' => FlightTicketSbu::findOrFail($id), 'label' => 'SBU tiket pesawat', 'type' => $type],
            'lodging_regional' => ['entry' => LodgingSbu::findOrFail($id), 'label' => 'SBU penginapan dalam daerah', 'type' => $type],
            'lodging_national' => ['entry' => NationalLodgingSbu::findOrFail($id), 'label' => 'SBU penginapan luar daerah', 'type' => $type],
            'representation' => ['entry' => RepresentationSbu::findOrFail($id), 'label' => 'SBU uang representasi', 'type' => $type],
            'daily_allowance' => ['entry' => DailyAllowanceSbu::findOrFail($id), 'label' => 'SBU uang harian', 'type' => $type],
            default => abort(404),
        };
    }
}
