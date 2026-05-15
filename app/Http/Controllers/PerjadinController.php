<?php

namespace App\Http\Controllers;

use App\Models\PerjadinEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PerjadinController extends Controller
{
    private const PERIOD_MONTHS = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    private const CATEGORY_OPTIONS = [
        'Perjadin Luar Provinsi',
        'Perjadin Luar Kota Dalam Provinsi',
        'Perjadin Dalam Kota',
    ];

    private const GRADE_OPTIONS = [
        '3A', '3B', '3C', '3D',
        '4A', '4B', '4C', '4D',
    ];

    private const TRANSPORT_OPTIONS = [
        'Pesawat',
        'Kapal',
        'Speed',
        'Kereta',
        'Bus',
        'Lainnya',
    ];

    public function index(Request $request): View
    {
        $period = $this->selectedPeriod($request);
        $selectedCategory = $request->string('category')->toString();
        $selectedKeyword = trim($request->string('keyword')->toString());

        if (! in_array($selectedCategory, self::CATEGORY_OPTIONS, true)) {
            $selectedCategory = '';
        }

        $entriesQuery = PerjadinEntry::query()
            ->whereYear('start_date', $period['year'])
            ->whereMonth('start_date', $period['month'])
            ->when($selectedCategory !== '', fn ($query) => $query->where('category', $selectedCategory))
            ->when($selectedKeyword !== '', function ($query) use ($selectedKeyword) {
                $query->where(function ($innerQuery) use ($selectedKeyword) {
                    $innerQuery
                        ->where('executor_name', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('assignment_number', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('destination_city', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('skpd_name', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('position_name', 'like', '%'.$selectedKeyword.'%');
                });
            })
            ->latest('assignment_date')
            ->latest('start_date')
            ->latest();

        $entries = $entriesQuery->get();
        $groupedEntries = collect(self::CATEGORY_OPTIONS)->map(function (string $category) use ($entries): array {
            $items = $entries->where('category', $category)->values();

            return [
                'label' => $category,
                'count' => $items->count(),
                'grand_total' => (int) $items->sum('grand_total'),
                'items' => $items,
            ];
        })->all();

        $categorySummary = collect(self::CATEGORY_OPTIONS)->map(function (string $category) use ($entries): array {
            $items = $entries->where('category', $category)->values();

            return [
                'label' => $category,
                'count' => $items->count(),
                'grand_total' => (int) $items->sum('grand_total'),
                'complete_count' => $items->filter(fn (PerjadinEntry $entry) => $entry->activity_file_path && $entry->receipt_file_path)->count(),
            ];
        })->all();

        return view('perjadin', [
            'title' => 'Perjadin',
            'categories' => self::CATEGORY_OPTIONS,
            'selectedCategory' => $selectedCategory,
            'selectedKeyword' => $selectedKeyword,
            'currentPeriod' => $period,
            'periodLabel' => $period['label'].' '.$period['year'],
            'monthOptions' => $this->monthOptions(),
            'yearOptions' => $this->yearOptions(),
            'groupedEntries' => $groupedEntries,
            'categorySummary' => $categorySummary,
            'summary' => [
                'totalCount' => $entries->count(),
                'totalGrandTotal' => (int) $entries->sum('grand_total'),
                'completeDocuments' => $entries->filter(fn (PerjadinEntry $entry) => $entry->activity_file_path && $entry->receipt_file_path)->count(),
                'activeCategories' => $entries->pluck('category')->unique()->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return $this->formView(
            'Tambah Perjadin',
            null,
            $this->selectedPeriod($request),
            $request->string('category')->toString(),
            trim($request->string('keyword')->toString())
        );
    }

    public function show(Request $request, PerjadinEntry $perjadinEntry): View
    {
        $period = [
            'month' => optional($perjadinEntry->start_date)->month ?? now()->month,
            'year' => optional($perjadinEntry->start_date)->year ?? now()->year,
            'label' => self::PERIOD_MONTHS[optional($perjadinEntry->start_date)->month ?? now()->month],
        ];

        return view('perjadin-show', [
            'title' => 'Detail Perjadin',
            'entry' => $perjadinEntry,
            'currentPeriod' => $period,
            'periodLabel' => $period['label'].' '.$period['year'],
            'activeCategory' => $request->string('category')->toString(),
            'activeKeyword' => trim($request->string('keyword')->toString()),
            'costGroups' => $this->costGroups($perjadinEntry),
            'receiptDefaults' => [
                'receipt_number' => old('receipt_number', ''),
                'received_from' => old('received_from', $perjadinEntry->skpd_name),
                'payment_purpose' => old('payment_purpose', 'Biaya perjalanan dinas '.$perjadinEntry->category.' tujuan '.$perjadinEntry->destination_city),
                'receipt_place' => old('receipt_place', $perjadinEntry->signature_location ?: $perjadinEntry->destination_city),
                'receipt_date' => old('receipt_date', optional($perjadinEntry->assignment_date)->format('Y-m-d') ?: now()->format('Y-m-d')),
                'recipient_name' => old('recipient_name', $perjadinEntry->executor_name),
                'recipient_position' => old('recipient_position', $perjadinEntry->position_name),
            ],
        ]);
    }

    public function downloadReceiptPdf(Request $request, PerjadinEntry $perjadinEntry)
    {
        $data = $request->validate([
            'receipt_number' => ['required', 'string', 'max:255'],
            'received_from' => ['required', 'string', 'max:255'],
            'payment_purpose' => ['required', 'string', 'max:1000'],
            'receipt_place' => ['required', 'string', 'max:255'],
            'receipt_date' => ['required', 'date'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_position' => ['nullable', 'string', 'max:255'],
        ]);

        $grandTotal = (int) $perjadinEntry->grand_total;

        $pdf = Pdf::loadView('pdf.perjadin-receipt', [
            'entry' => $perjadinEntry,
            'receiptNumber' => $data['receipt_number'],
            'receivedFrom' => $data['received_from'],
            'paymentPurpose' => $data['payment_purpose'],
            'receiptPlace' => $data['receipt_place'],
            'receiptDate' => $data['receipt_date'],
            'recipientName' => $data['recipient_name'],
            'recipientPosition' => $data['recipient_position'] ?: '-',
            'grandTotal' => $grandTotal,
            'grandTotalLabel' => $this->moneyLabel($grandTotal),
            'grandTotalWords' => ucfirst(trim($this->terbilang($grandTotal))).' rupiah',
        ])->setPaper('a4', 'portrait');

        return $pdf->download('kwitansi-perjadin-'.$perjadinEntry->id.'.pdf');
    }

    public function downloadDetailPdf(Request $request, PerjadinEntry $perjadinEntry)
    {
        $pdf = Pdf::loadView('pdf.perjadin-detail', [
            'entry' => $perjadinEntry,
            'costGroups' => $this->costGroups($perjadinEntry),
            'periodLabel' => optional($perjadinEntry->start_date)->translatedFormat('d M Y').' - '.optional($perjadinEntry->end_date)->translatedFormat('d M Y'),
            'generatedAt' => now()->translatedFormat('d F Y H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('detail-perjadin-'.$perjadinEntry->id.'.pdf');
    }

    public function duplicate(Request $request, PerjadinEntry $perjadinEntry): RedirectResponse
    {
        $duplicate = $perjadinEntry->replicate([
            'activity_file_path',
            'activity_file_original_name',
            'receipt_file_path',
            'receipt_file_original_name',
            'created_at',
            'updated_at',
        ]);

        $duplicate->fill([
            'activity_file_path' => $this->duplicateStoredFile($perjadinEntry->activity_file_path, 'proofs/perjadin/activity'),
            'activity_file_original_name' => $perjadinEntry->activity_file_original_name,
            'receipt_file_path' => $this->duplicateStoredFile($perjadinEntry->receipt_file_path, 'proofs/perjadin/receipts'),
            'receipt_file_original_name' => $perjadinEntry->receipt_file_original_name,
            'created_by' => $request->user()->id,
        ]);

        $duplicate->save();

        return redirect()->route('perjadin.edit', [
            'perjadinEntry' => $duplicate,
            'month' => optional($duplicate->start_date)->month ?? now()->month,
            'year' => optional($duplicate->start_date)->year ?? now()->year,
            'category' => $request->string('category')->toString() ?: $duplicate->category,
            'keyword' => trim($request->string('keyword')->toString()),
        ])->with('status', 'Data perjadin berhasil diduplikat.');
    }

    public function edit(Request $request, PerjadinEntry $perjadinEntry): View
    {
        $period = [
            'month' => optional($perjadinEntry->start_date)->month ?? now()->month,
            'year' => optional($perjadinEntry->start_date)->year ?? now()->year,
            'label' => self::PERIOD_MONTHS[optional($perjadinEntry->start_date)->month ?? now()->month],
        ];

        return $this->formView(
            'Edit Perjadin',
            $perjadinEntry,
            $period,
            $request->string('category')->toString(),
            trim($request->string('keyword')->toString())
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        $activityFile = $this->storePdf($request, 'activity_file', 'proofs/perjadin/activity');
        $receiptFile = $this->storePdf($request, 'receipt_file', 'proofs/perjadin/receipts');

        PerjadinEntry::create([
            ...$data,
            'activity_file_path' => $activityFile['path'],
            'activity_file_original_name' => $activityFile['name'],
            'receipt_file_path' => $receiptFile['path'],
            'receipt_file_original_name' => $receiptFile['name'],
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('perjadin', [
            'month' => (int) date('n', strtotime($data['start_date'])),
            'year' => (int) date('Y', strtotime($data['start_date'])),
            'category' => $data['category'],
            'keyword' => trim($request->string('keyword')->toString()),
        ])->with('status', 'Data perjadin berhasil disimpan.');
    }

    public function update(Request $request, PerjadinEntry $perjadinEntry): RedirectResponse
    {
        $data = $this->validatedData($request);
        $activityFile = $this->storePdf($request, 'activity_file', 'proofs/perjadin/activity');
        $receiptFile = $this->storePdf($request, 'receipt_file', 'proofs/perjadin/receipts');

        $oldActivityPath = $perjadinEntry->activity_file_path;
        $oldReceiptPath = $perjadinEntry->receipt_file_path;

        $perjadinEntry->update([
            ...$data,
            'activity_file_path' => $activityFile['path'] ?? $perjadinEntry->activity_file_path,
            'activity_file_original_name' => $activityFile['name'] ?? $perjadinEntry->activity_file_original_name,
            'receipt_file_path' => $receiptFile['path'] ?? $perjadinEntry->receipt_file_path,
            'receipt_file_original_name' => $receiptFile['name'] ?? $perjadinEntry->receipt_file_original_name,
        ]);

        if ($activityFile['path'] && $oldActivityPath) {
            Storage::disk('public')->delete($oldActivityPath);
        }

        if ($receiptFile['path'] && $oldReceiptPath) {
            Storage::disk('public')->delete($oldReceiptPath);
        }

        return redirect()->route('perjadin', [
            'month' => (int) date('n', strtotime($data['start_date'])),
            'year' => (int) date('Y', strtotime($data['start_date'])),
            'category' => $perjadinEntry->category,
            'keyword' => trim($request->string('keyword')->toString()),
        ])->with('status', 'Data perjadin berhasil diperbarui.');
    }

    public function destroy(PerjadinEntry $perjadinEntry): RedirectResponse
    {
        $category = $perjadinEntry->category;
        $activityPath = $perjadinEntry->activity_file_path;
        $receiptPath = $perjadinEntry->receipt_file_path;

        $perjadinEntry->delete();

        if ($activityPath) {
            Storage::disk('public')->delete($activityPath);
        }

        if ($receiptPath) {
            Storage::disk('public')->delete($receiptPath);
        }

        return redirect()->route('perjadin', [
            'month' => (int) request()->integer('month', optional($perjadinEntry->start_date)->month ?? now()->month),
            'year' => (int) request()->integer('year', optional($perjadinEntry->start_date)->year ?? now()->year),
            'category' => request()->string('category')->toString() ?: $category,
            'keyword' => trim(request()->string('keyword')->toString()),
        ])->with('status', 'Data perjadin berhasil dihapus.');
    }

    public function showAttachment(PerjadinEntry $perjadinEntry, string $attachment)
    {
        $path = match ($attachment) {
            'activity' => $perjadinEntry->activity_file_path,
            'receipt' => $perjadinEntry->receipt_file_path,
            default => null,
        };

        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('public')->path($path),
            [
                'Content-Type' => Storage::disk('public')->mimeType($path) ?: 'application/pdf',
            ]
        );
    }

    private function validatedData(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'category' => ['required', 'string', Rule::in(self::CATEGORY_OPTIONS)],
            'skpd_name' => ['required', 'string', 'max:255'],
            'executor_name' => ['required', 'string', 'max:255'],
            'position_name' => ['required', 'string', 'max:255'],
            'grade' => ['required', 'string', Rule::in(self::GRADE_OPTIONS)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'assignment_number' => ['required', 'string', 'max:255'],
            'assignment_date' => ['required', 'date'],
            'signature_location' => ['required', 'string', 'max:255'],
            'destination_city' => ['required', 'string', 'max:255'],

            'daily_allowance_enabled' => ['nullable', 'boolean'],
            'daily_allowance_days' => ['nullable', 'integer', 'min:1'],
            'daily_allowance_rate' => ['nullable', 'string'],

            'representation_enabled' => ['nullable', 'boolean'],
            'representation_days' => ['nullable', 'integer', 'min:1'],
            'representation_rate' => ['nullable', 'string'],

            'ticket_enabled' => ['nullable', 'boolean'],
            'ticket_transport_type' => ['nullable', 'string', Rule::in(self::TRANSPORT_OPTIONS)],
            'ticket_departure_date' => ['nullable', 'date'],
            'ticket_return_date' => ['nullable', 'date', 'after_or_equal:ticket_departure_date'],
            'ticket_departure_price' => ['nullable', 'string'],
            'ticket_return_price' => ['nullable', 'string'],
            'ticket_departure_operator' => ['nullable', 'string', 'max:255'],
            'ticket_return_operator' => ['nullable', 'string', 'max:255'],
            'ticket_departure_number' => ['nullable', 'string', 'max:255'],
            'ticket_return_number' => ['nullable', 'string', 'max:255'],
            'ticket_departure_booking_code' => ['nullable', 'string', 'max:255'],
            'ticket_return_booking_code' => ['nullable', 'string', 'max:255'],

            'lodging_enabled' => ['nullable', 'boolean'],
            'lodging_nights' => ['nullable', 'integer', 'min:1'],
            'lodging_rate' => ['nullable', 'string'],
            'lodging_hotel_name' => ['nullable', 'string', 'max:255'],

            'local_transport_enabled' => ['nullable', 'boolean'],
            'local_transport_domicile_to_airport' => ['nullable', 'string'],
            'local_transport_airport_to_domicile' => ['nullable', 'string'],
            'local_transport_airport_to_hotel' => ['nullable', 'string'],
            'local_transport_hotel_to_airport' => ['nullable', 'string'],
            'local_transport_other' => ['nullable', 'string'],

            'other_cost_enabled' => ['nullable', 'boolean'],
            'other_cost_amount' => ['nullable', 'string'],

            'activity_file' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'receipt_file' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $checks = [
                'daily_allowance_enabled' => [
                    'daily_allowance_days' => 'Jumlah hari uang harian wajib diisi.',
                    'daily_allowance_rate' => 'Nominal uang harian wajib diisi.',
                ],
                'representation_enabled' => [
                    'representation_days' => 'Jumlah hari representasi wajib diisi.',
                    'representation_rate' => 'Nominal representasi wajib diisi.',
                ],
                'ticket_enabled' => [
                    'ticket_transport_type' => 'Jenis tiket wajib dipilih.',
                    'ticket_departure_date' => 'Tanggal berangkat tiket wajib diisi.',
                    'ticket_return_date' => 'Tanggal pulang tiket wajib diisi.',
                    'ticket_departure_price' => 'Harga tiket berangkat wajib diisi.',
                    'ticket_return_price' => 'Harga tiket kembali wajib diisi.',
                ],
                'lodging_enabled' => [
                    'lodging_nights' => 'Jumlah malam penginapan wajib diisi.',
                    'lodging_rate' => 'Nominal penginapan wajib diisi.',
                ],
                'other_cost_enabled' => [
                    'other_cost_amount' => 'Biaya lain-lain wajib diisi.',
                ],
            ];

            foreach ($checks as $toggle => $fields) {
                if (! $request->boolean($toggle)) {
                    continue;
                }

                foreach ($fields as $field => $message) {
                    if (blank($request->input($field))) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }
        });

        $validated = $validator->validate();

        $dailyAllowanceEnabled = (bool) ($validated['daily_allowance_enabled'] ?? false);
        $representationEnabled = (bool) ($validated['representation_enabled'] ?? false);
        $ticketEnabled = (bool) ($validated['ticket_enabled'] ?? false);
        $lodgingEnabled = (bool) ($validated['lodging_enabled'] ?? false);
        $localTransportEnabled = (bool) ($validated['local_transport_enabled'] ?? false);
        $otherCostEnabled = (bool) ($validated['other_cost_enabled'] ?? false);

        $dailyAllowanceDays = $dailyAllowanceEnabled ? (int) ($validated['daily_allowance_days'] ?? 0) : null;
        $dailyAllowanceRate = $dailyAllowanceEnabled ? $this->parseMoney($validated['daily_allowance_rate'] ?? null) : null;
        $dailyAllowanceTotal = $dailyAllowanceEnabled ? $dailyAllowanceDays * $dailyAllowanceRate : 0;

        $representationDays = $representationEnabled ? (int) ($validated['representation_days'] ?? 0) : null;
        $representationRate = $representationEnabled ? $this->parseMoney($validated['representation_rate'] ?? null) : null;
        $representationTotal = $representationEnabled ? $representationDays * $representationRate : 0;

        $ticketDeparturePrice = $ticketEnabled ? $this->parseMoney($validated['ticket_departure_price'] ?? null) : null;
        $ticketReturnPrice = $ticketEnabled ? $this->parseMoney($validated['ticket_return_price'] ?? null) : null;
        $ticketTotal = $ticketEnabled ? $ticketDeparturePrice + $ticketReturnPrice : 0;

        $lodgingNights = $lodgingEnabled ? (int) ($validated['lodging_nights'] ?? 0) : null;
        $lodgingRate = $lodgingEnabled ? $this->parseMoney($validated['lodging_rate'] ?? null) : null;
        $lodgingTotal = $lodgingEnabled ? $lodgingNights * $lodgingRate : 0;

        $localTransportDomicileToAirport = $localTransportEnabled ? $this->parseMoney($validated['local_transport_domicile_to_airport'] ?? null) : null;
        $localTransportAirportToDomicile = $localTransportEnabled ? $this->parseMoney($validated['local_transport_airport_to_domicile'] ?? null) : null;
        $localTransportAirportToHotel = $localTransportEnabled ? $this->parseMoney($validated['local_transport_airport_to_hotel'] ?? null) : null;
        $localTransportHotelToAirport = $localTransportEnabled ? $this->parseMoney($validated['local_transport_hotel_to_airport'] ?? null) : null;
        $localTransportOther = $localTransportEnabled ? $this->parseMoney($validated['local_transport_other'] ?? null) : null;
        $localTransportTotal = $localTransportEnabled
            ? $localTransportDomicileToAirport + $localTransportAirportToDomicile + $localTransportAirportToHotel + $localTransportHotelToAirport + $localTransportOther
            : 0;

        $otherCostAmount = $otherCostEnabled ? $this->parseMoney($validated['other_cost_amount'] ?? null) : null;
        $grandTotal = $dailyAllowanceTotal + $representationTotal + $ticketTotal + $lodgingTotal + $localTransportTotal + ($otherCostAmount ?? 0);

        return [
            'category' => $validated['category'],
            'skpd_name' => $validated['skpd_name'],
            'executor_name' => $validated['executor_name'],
            'position_name' => $validated['position_name'],
            'grade' => $validated['grade'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'assignment_number' => $validated['assignment_number'],
            'assignment_date' => $validated['assignment_date'],
            'signature_location' => $validated['signature_location'],
            'destination_city' => $validated['destination_city'],

            'daily_allowance_enabled' => $dailyAllowanceEnabled,
            'daily_allowance_days' => $dailyAllowanceDays,
            'daily_allowance_rate' => $dailyAllowanceRate,
            'daily_allowance_total' => $dailyAllowanceTotal,

            'representation_enabled' => $representationEnabled,
            'representation_days' => $representationDays,
            'representation_rate' => $representationRate,
            'representation_total' => $representationTotal,

            'ticket_enabled' => $ticketEnabled,
            'ticket_transport_type' => $ticketEnabled ? $validated['ticket_transport_type'] : null,
            'ticket_departure_date' => $ticketEnabled ? ($validated['ticket_departure_date'] ?? null) : null,
            'ticket_return_date' => $ticketEnabled ? ($validated['ticket_return_date'] ?? null) : null,
            'ticket_departure_price' => $ticketDeparturePrice,
            'ticket_return_price' => $ticketReturnPrice,
            'ticket_total' => $ticketTotal,
            'ticket_departure_operator' => $ticketEnabled ? ($validated['ticket_departure_operator'] ?? null) : null,
            'ticket_return_operator' => $ticketEnabled ? ($validated['ticket_return_operator'] ?? null) : null,
            'ticket_departure_number' => $ticketEnabled ? ($validated['ticket_departure_number'] ?? null) : null,
            'ticket_return_number' => $ticketEnabled ? ($validated['ticket_return_number'] ?? null) : null,
            'ticket_departure_booking_code' => $ticketEnabled ? ($validated['ticket_departure_booking_code'] ?? null) : null,
            'ticket_return_booking_code' => $ticketEnabled ? ($validated['ticket_return_booking_code'] ?? null) : null,

            'lodging_enabled' => $lodgingEnabled,
            'lodging_nights' => $lodgingNights,
            'lodging_rate' => $lodgingRate,
            'lodging_total' => $lodgingTotal,
            'lodging_hotel_name' => $lodgingEnabled ? ($validated['lodging_hotel_name'] ?? null) : null,

            'local_transport_enabled' => $localTransportEnabled,
            'local_transport_domicile_to_airport' => $localTransportDomicileToAirport,
            'local_transport_airport_to_domicile' => $localTransportAirportToDomicile,
            'local_transport_airport_to_hotel' => $localTransportAirportToHotel,
            'local_transport_hotel_to_airport' => $localTransportHotelToAirport,
            'local_transport_other' => $localTransportOther,
            'local_transport_total' => $localTransportTotal,

            'other_cost_enabled' => $otherCostEnabled,
            'other_cost_amount' => $otherCostAmount,
            'grand_total' => $grandTotal,
        ];
    }

    private function storePdf(Request $request, string $field, string $directory): array
    {
        if (! $request->hasFile($field)) {
            return [
                'path' => null,
                'name' => null,
            ];
        }

        return [
            'path' => $request->file($field)->store($directory, 'public'),
            'name' => $request->file($field)->getClientOriginalName(),
        ];
    }

    private function parseMoney(null|string|int $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        return (int) preg_replace('/\D/', '', (string) $value);
    }

    private function duplicateStoredFile(?string $path, string $directory): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $duplicatePath = trim($directory, '/').'/'.uniqid('copy_', true).($extension ? '.'.$extension : '');

        Storage::disk('public')->copy($path, $duplicatePath);

        return $duplicatePath;
    }

    private function formView(
        string $title,
        ?PerjadinEntry $entry = null,
        ?array $period = null,
        string $activeCategory = '',
        string $activeKeyword = ''
    ): View
    {
        $period ??= [
            'month' => now()->month,
            'year' => now()->year,
            'label' => self::PERIOD_MONTHS[now()->month],
        ];

        return view('add-perjadin', [
            'title' => $title,
            'entry' => $entry,
            'currentPeriod' => $period,
            'periodLabel' => $period['label'].' '.$period['year'],
            'defaultStartDate' => sprintf('%04d-%02d-01', $period['year'], $period['month']),
            'activeCategory' => in_array($activeCategory, self::CATEGORY_OPTIONS, true) ? $activeCategory : '',
            'activeKeyword' => $activeKeyword,
            'categories' => self::CATEGORY_OPTIONS,
            'gradeOptions' => self::GRADE_OPTIONS,
            'transportTypes' => self::TRANSPORT_OPTIONS,
        ]);
    }

    private function selectedPeriod(Request $request): array
    {
        $month = (int) $request->integer('month', now()->month);
        $year = (int) $request->integer('year', now()->year);

        if (! array_key_exists($month, self::PERIOD_MONTHS)) {
            $month = now()->month;
        }

        return [
            'month' => $month,
            'year' => $year,
            'label' => self::PERIOD_MONTHS[$month],
        ];
    }

    private function monthOptions(): array
    {
        return collect(self::PERIOD_MONTHS)
            ->map(fn (string $label, int $month) => [
                'value' => $month,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    private function yearOptions(): array
    {
        $currentYear = now()->year;

        return collect(range($currentYear - 2, $currentYear + 5))
            ->values()
            ->all();
    }

    private function costGroups(PerjadinEntry $entry): array
    {
        return [
            [
                'title' => 'Uang Harian',
                'enabled' => $entry->daily_allowance_enabled,
                'rows' => [
                    ['label' => 'Jumlah Hari', 'value' => $entry->daily_allowance_days ?: '-'],
                    ['label' => 'Uang Harian', 'value' => $this->moneyLabel($entry->daily_allowance_rate)],
                    ['label' => 'Total', 'value' => $this->moneyLabel($entry->daily_allowance_total)],
                ],
            ],
            [
                'title' => 'Representasi',
                'enabled' => $entry->representation_enabled,
                'rows' => [
                    ['label' => 'Jumlah Hari', 'value' => $entry->representation_days ?: '-'],
                    ['label' => 'Nominal Sesuai SPPD', 'value' => $this->moneyLabel($entry->representation_rate)],
                    ['label' => 'Total', 'value' => $this->moneyLabel($entry->representation_total)],
                ],
            ],
            [
                'title' => 'Tiket',
                'enabled' => $entry->ticket_enabled,
                'rows' => [
                    ['label' => 'Jenis Transport', 'value' => $entry->ticket_transport_type ?: '-'],
                    ['label' => 'Tanggal Berangkat', 'value' => optional($entry->ticket_departure_date)->translatedFormat('d M Y') ?: '-'],
                    ['label' => 'Tanggal Pulang', 'value' => optional($entry->ticket_return_date)->translatedFormat('d M Y') ?: '-'],
                    ['label' => 'Harga Tiket Berangkat', 'value' => $this->moneyLabel($entry->ticket_departure_price)],
                    ['label' => 'Harga Tiket Kembali', 'value' => $this->moneyLabel($entry->ticket_return_price)],
                    ['label' => 'Operator Berangkat', 'value' => $entry->ticket_departure_operator ?: '-'],
                    ['label' => 'Operator Kembali', 'value' => $entry->ticket_return_operator ?: '-'],
                    ['label' => 'Nomor Tiket Berangkat', 'value' => $entry->ticket_departure_number ?: '-'],
                    ['label' => 'Nomor Tiket Pulang', 'value' => $entry->ticket_return_number ?: '-'],
                    ['label' => 'Kode Booking Berangkat', 'value' => $entry->ticket_departure_booking_code ?: '-'],
                    ['label' => 'Kode Booking Pulang', 'value' => $entry->ticket_return_booking_code ?: '-'],
                    ['label' => 'Total', 'value' => $this->moneyLabel($entry->ticket_total)],
                ],
            ],
            [
                'title' => 'Penginapan',
                'enabled' => $entry->lodging_enabled,
                'rows' => [
                    ['label' => 'Jumlah Malam', 'value' => $entry->lodging_nights ?: '-'],
                    ['label' => 'Nominal Sesuai SPPD', 'value' => $this->moneyLabel($entry->lodging_rate)],
                    ['label' => 'Nama Hotel', 'value' => $entry->lodging_hotel_name ?: '-'],
                    ['label' => 'Total', 'value' => $this->moneyLabel($entry->lodging_total)],
                ],
            ],
            [
                'title' => 'Transportasi Lokal',
                'enabled' => $entry->local_transport_enabled,
                'rows' => [
                    ['label' => 'Domisili ke Bandara', 'value' => $this->moneyLabel($entry->local_transport_domicile_to_airport)],
                    ['label' => 'Bandara ke Domisili', 'value' => $this->moneyLabel($entry->local_transport_airport_to_domicile)],
                    ['label' => 'Bandara ke Hotel', 'value' => $this->moneyLabel($entry->local_transport_airport_to_hotel)],
                    ['label' => 'Hotel ke Bandara', 'value' => $this->moneyLabel($entry->local_transport_hotel_to_airport)],
                    ['label' => 'Lain-lain', 'value' => $this->moneyLabel($entry->local_transport_other)],
                    ['label' => 'Total', 'value' => $this->moneyLabel($entry->local_transport_total)],
                ],
            ],
            [
                'title' => 'Biaya Lain-lain',
                'enabled' => $entry->other_cost_enabled,
                'rows' => [
                    ['label' => 'Biaya Lain-lain', 'value' => $this->moneyLabel($entry->other_cost_amount)],
                ],
            ],
        ];
    }

    private function moneyLabel(?int $value): string
    {
        if ($value === null) {
            return '-';
        }

        return 'Rp '.number_format($value, 0, ',', '.');
    }

    private function receiptNumber(PerjadinEntry $entry): string
    {
        $month = optional($entry->assignment_date)->month ?? now()->month;
        $year = optional($entry->assignment_date)->year ?? now()->year;

        return 'KW/'.str_pad((string) $entry->id, 4, '0', STR_PAD_LEFT).'/PERJADIN/'.$this->romanMonth($month).'/'.$year;
    }

    private function romanMonth(int $month): string
    {
        return [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ][$month] ?? 'I';
    }

    private function terbilang(int $value): string
    {
        $value = abs($value);
        $words = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

        if ($value < 12) {
            return ' '.$words[$value];
        }

        if ($value < 20) {
            return $this->terbilang($value - 10).' belas';
        }

        if ($value < 100) {
            return $this->terbilang((int) floor($value / 10)).' puluh'.$this->terbilang($value % 10);
        }

        if ($value < 200) {
            return ' seratus'.$this->terbilang($value - 100);
        }

        if ($value < 1000) {
            return $this->terbilang((int) floor($value / 100)).' ratus'.$this->terbilang($value % 100);
        }

        if ($value < 2000) {
            return ' seribu'.$this->terbilang($value - 1000);
        }

        if ($value < 1000000) {
            return $this->terbilang((int) floor($value / 1000)).' ribu'.$this->terbilang($value % 1000);
        }

        if ($value < 1000000000) {
            return $this->terbilang((int) floor($value / 1000000)).' juta'.$this->terbilang($value % 1000000);
        }

        if ($value < 1000000000000) {
            return $this->terbilang((int) floor($value / 1000000000)).' miliar'.$this->terbilang($value % 1000000000);
        }

        return $this->terbilang((int) floor($value / 1000000000000)).' triliun'.$this->terbilang($value % 1000000000000);
    }
}
