<?php

namespace App\Http\Controllers;

use App\Models\DailyAllowanceSbu;
use App\Models\FlightTicketSbu;
use App\Models\LodgingSbu;
use App\Models\LocalTransportSbu;
use App\Models\NationalLodgingSbu;
use App\Models\PerjadinEntry;
use App\Models\RepresentationSbu;
use App\Models\TravelDestinationRegion;
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
        'Perjadin Luar Daerah',
        'Perjadin Dalam Daerah',
    ];

    private const GRADE_OPTIONS = [
        '3A', '3B', '3C', '3D',
        '4A', '4B', '4C', '4D',
    ];

    private const ECHELON_OPTIONS = [
        'Non Eselon', '1', '2', '3', '4',
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
            ->with(['creator:id,name', 'updater:id,name'])
            ->whereYear('start_date', $period['year'])
            ->whereMonth('start_date', $period['month'])
            ->when($selectedCategory !== '', fn ($query) => $query->where('category', $selectedCategory))
            ->when($selectedKeyword !== '', function ($query) use ($selectedKeyword) {
                $query->where(function ($innerQuery) use ($selectedKeyword) {
                    $innerQuery
                        ->where('executor_name', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('assignment_number', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('destination_city', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('origin_regency', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('origin_district', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('destination_regency', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('destination_district', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('skpd_name', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('position_name', 'like', '%'.$selectedKeyword.'%');
                });
            })
            ->latest('assignment_date')
            ->latest('start_date')
            ->latest();

        $entries = $entriesQuery->get();
        $comparisonContext = [
            'airportTaxiReference' => $this->airportTaxiReference(),
            'localTransportReferences' => $this->localTransportReferences(),
            'flightTicketReferences' => $this->flightTicketReferences(),
            'regionalLodgingReferences' => $this->regionalLodgingReferences(),
            'nationalLodgingReferences' => $this->nationalLodgingReferences(),
            'representationReferences' => $this->representationReferences(),
            'dailyAllowanceReferences' => $this->dailyAllowanceReferences(),
            'destinationOptions' => $this->outsideRegionDestinationOptions(),
        ];

        $entries->each(function (PerjadinEntry $entry) use ($comparisonContext): void {
            $entry->sbu_comparison_summary = $this->entrySbuComparisonSummary($entry, $comparisonContext);
        });

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
                'complete_count' => $items->filter(fn (PerjadinEntry $entry) => $entry->activity_file_path && $entry->receipt_file_path && $entry->report_file_path)->count(),
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
                'completeDocuments' => $entries->filter(fn (PerjadinEntry $entry) => $entry->activity_file_path && $entry->receipt_file_path && $entry->report_file_path)->count(),
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
        $perjadinEntry->loadMissing(['creator:id,name', 'updater:id,name']);

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
            'receiptBreakdown' => $this->receiptBreakdown($perjadinEntry),
            'receiptDefaults' => [
                'receipt_number' => old('receipt_number', ''),
                'received_from' => old('received_from', $perjadinEntry->skpd_name),
                'payment_purpose' => old('payment_purpose', 'Biaya perjalanan dinas '.$perjadinEntry->category.' tujuan '.$perjadinEntry->destination_city),
                'receipt_place' => old('receipt_place', $perjadinEntry->signature_location ?: $perjadinEntry->destination_city),
                'receipt_date' => old('receipt_date', optional($perjadinEntry->assignment_date)->format('Y-m-d') ?: now()->format('Y-m-d')),
                'recipient_name' => old('recipient_name', $perjadinEntry->executor_name),
                'recipient_nip' => old('recipient_nip', ''),
                'approver_name' => old('approver_name', ''),
                'approver_nip' => old('approver_nip', ''),
                'treasurer_name' => old('treasurer_name', ''),
                'treasurer_nip' => old('treasurer_nip', ''),
            ],
        ]);
    }

    public function downloadReceiptPdf(Request $request, PerjadinEntry $perjadinEntry)
    {
        $perjadinEntry->loadMissing(['creator:id,name', 'updater:id,name']);

        $data = $request->validate([
            'receipt_number' => ['required', 'string', 'max:255'],
            'received_from' => ['required', 'string', 'max:255'],
            'payment_purpose' => ['required', 'string', 'max:1000'],
            'receipt_place' => ['required', 'string', 'max:255'],
            'receipt_date' => ['required', 'date'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_nip' => ['nullable', 'string', 'max:255'],
            'approver_name' => ['nullable', 'string', 'max:255'],
            'approver_nip' => ['nullable', 'string', 'max:255'],
            'treasurer_name' => ['nullable', 'string', 'max:255'],
            'treasurer_nip' => ['nullable', 'string', 'max:255'],
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
            'recipientNip' => $data['recipient_nip'] ?: '-',
            'approverName' => $data['approver_name'] ?: '........................................',
            'approverNip' => $data['approver_nip'] ?: '........................................',
            'treasurerName' => $data['treasurer_name'] ?: '........................................',
            'treasurerNip' => $data['treasurer_nip'] ?: '........................................',
            'grandTotal' => $grandTotal,
            'grandTotalLabel' => $this->moneyLabel($grandTotal),
            'grandTotalWords' => ucfirst(trim($this->terbilang($grandTotal))).' rupiah',
            'receiptBreakdown' => $this->receiptBreakdown($perjadinEntry),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('kwitansi-perjadin-'.$perjadinEntry->id.'.pdf');
    }

    public function downloadDetailPdf(Request $request, PerjadinEntry $perjadinEntry)
    {
        $perjadinEntry->loadMissing(['creator:id,name', 'updater:id,name']);

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
            'report_file_path',
            'report_file_original_name',
            'created_at',
            'updated_at',
        ]);

        $duplicate->fill([
            'activity_file_path' => $this->duplicateStoredFile($perjadinEntry->activity_file_path, 'proofs/perjadin/activity'),
            'activity_file_original_name' => $perjadinEntry->activity_file_original_name,
            'receipt_file_path' => $this->duplicateStoredFile($perjadinEntry->receipt_file_path, 'proofs/perjadin/receipts'),
            'receipt_file_original_name' => $perjadinEntry->receipt_file_original_name,
            'report_file_path' => $this->duplicateStoredFile($perjadinEntry->report_file_path, 'proofs/perjadin/reports'),
            'report_file_original_name' => $perjadinEntry->report_file_original_name,
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
        $perjadinEntry->loadMissing(['creator:id,name', 'updater:id,name']);

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
        $reportFile = $this->storePdf($request, 'report_file', 'proofs/perjadin/reports');

        PerjadinEntry::create([
            ...$data,
            'activity_file_path' => $activityFile['path'],
            'activity_file_original_name' => $activityFile['name'],
            'receipt_file_path' => $receiptFile['path'],
            'receipt_file_original_name' => $receiptFile['name'],
            'report_file_path' => $reportFile['path'],
            'report_file_original_name' => $reportFile['name'],
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
        $reportFile = $this->storePdf($request, 'report_file', 'proofs/perjadin/reports');
        $removeActivityFile = $request->boolean('remove_activity_file');
        $removeReceiptFile = $request->boolean('remove_receipt_file');
        $removeReportFile = $request->boolean('remove_report_file');

        $oldActivityPath = $perjadinEntry->activity_file_path;
        $oldReceiptPath = $perjadinEntry->receipt_file_path;
        $oldReportPath = $perjadinEntry->report_file_path;
        $nextActivityPath = $activityFile['path'] ?? ($removeActivityFile ? null : $perjadinEntry->activity_file_path);
        $nextActivityName = $activityFile['name'] ?? ($removeActivityFile ? null : $perjadinEntry->activity_file_original_name);
        $nextReceiptPath = $receiptFile['path'] ?? ($removeReceiptFile ? null : $perjadinEntry->receipt_file_path);
        $nextReceiptName = $receiptFile['name'] ?? ($removeReceiptFile ? null : $perjadinEntry->receipt_file_original_name);
        $nextReportPath = $reportFile['path'] ?? ($removeReportFile ? null : $perjadinEntry->report_file_path);
        $nextReportName = $reportFile['name'] ?? ($removeReportFile ? null : $perjadinEntry->report_file_original_name);

        $perjadinEntry->update([
            ...$data,
            'activity_file_path' => $nextActivityPath,
            'activity_file_original_name' => $nextActivityName,
            'receipt_file_path' => $nextReceiptPath,
            'receipt_file_original_name' => $nextReceiptName,
            'report_file_path' => $nextReportPath,
            'report_file_original_name' => $nextReportName,
            'updated_by' => $request->user()->id,
        ]);

        if (($activityFile['path'] || $removeActivityFile) && $oldActivityPath) {
            Storage::disk('public')->delete($oldActivityPath);
        }

        if (($receiptFile['path'] || $removeReceiptFile) && $oldReceiptPath) {
            Storage::disk('public')->delete($oldReceiptPath);
        }

        if (($reportFile['path'] || $removeReportFile) && $oldReportPath) {
            Storage::disk('public')->delete($oldReportPath);
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
        abort_unless(auth()->user()?->hasAnyRole(['admin', 'bendahara']), 403);

        $category = $perjadinEntry->category;
        $activityPath = $perjadinEntry->activity_file_path;
        $receiptPath = $perjadinEntry->receipt_file_path;
        $reportPath = $perjadinEntry->report_file_path;

        $perjadinEntry->delete();

        if ($activityPath) {
            Storage::disk('public')->delete($activityPath);
        }

        if ($receiptPath) {
            Storage::disk('public')->delete($receiptPath);
        }

        if ($reportPath) {
            Storage::disk('public')->delete($reportPath);
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
            'report' => $perjadinEntry->report_file_path,
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
            'echelon_level' => ['required', 'string', Rule::in(self::ECHELON_OPTIONS)],
            'grade' => ['required', 'string', Rule::in(self::GRADE_OPTIONS)],
            'origin_regency' => ['nullable', 'string'],
            'origin_district' => ['nullable', 'string', 'max:255'],
            'destination_regency' => ['nullable', 'string'],
            'destination_district' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'assignment_number' => ['required', 'string', 'max:255'],
            'assignment_date' => ['required', 'date'],
            'signature_location' => ['required', 'string', 'max:255'],
            'destination_city' => ['nullable', 'string', 'max:255'],
            'regional_trip_scope' => ['nullable', 'string', Rule::in(['dalam_kota_sofifi', 'luar_kota_sofifi'])],
            'sofifi_over_8_hours' => ['nullable', 'boolean'],

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
            'lodging_has_receipt' => ['nullable', 'boolean'],
            'lodging_nights' => ['nullable', 'integer', 'min:1'],
            'lodging_rate' => ['nullable', 'string'],
            'lodging_hotel_name' => ['nullable', 'string', 'max:255'],

            'local_transport_enabled' => ['nullable', 'boolean'],
            'local_transport_segment_ids' => ['nullable', 'array'],
            'local_transport_segment_ids.*' => ['nullable', 'integer'],
            'local_transport_domicile_to_airport' => ['nullable', 'string'],
            'local_transport_airport_to_domicile' => ['nullable', 'string'],
            'local_transport_airport_to_hotel' => ['nullable', 'string'],
            'local_transport_hotel_to_airport' => ['nullable', 'string'],
            'local_transport_other' => ['nullable', 'string'],

            'other_cost_enabled' => ['nullable', 'boolean'],
            'other_cost_amount' => ['nullable', 'string'],

            'activity_file' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'receipt_file' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'report_file' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'remove_activity_file' => ['nullable', 'boolean'],
            'remove_receipt_file' => ['nullable', 'boolean'],
            'remove_report_file' => ['nullable', 'boolean'],
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

            if ($request->input('category') === 'Perjadin Dalam Daerah') {
                foreach ([
                    'origin_regency' => 'Kabupaten asal wajib dipilih.',
                    'origin_district' => 'Kecamatan asal wajib dipilih.',
                    'destination_regency' => 'Kabupaten tujuan wajib dipilih.',
                    'destination_district' => 'Kecamatan tujuan wajib dipilih.',
                ] as $field => $message) {
                    if (blank($request->input($field))) {
                        $validator->errors()->add($field, $message);
                    }
                }

                $originChoices = $this->localTransportOriginChoices();
                $regionChoices = $this->localTransportRegionChoices();

                foreach (['origin', 'destination'] as $prefix) {
                    $regency = $request->input($prefix.'_regency');
                    $district = $request->input($prefix.'_district');
                    $allowedChoices = $prefix === 'origin' ? $originChoices : $regionChoices;
                    $allowedDistricts = $allowedChoices[$regency] ?? [];

                    if ($regency && ! array_key_exists($regency, $allowedChoices)) {
                        $validator->errors()->add($prefix.'_regency', 'Kabupaten '.($prefix === 'origin' ? 'asal' : 'tujuan').' harus dipilih dari daftar yang tersedia.');
                    }

                    if ($regency && $district && ! in_array($district, $allowedDistricts, true)) {
                        $validator->errors()->add($prefix.'_district', 'Kecamatan yang dipilih tidak sesuai dengan kabupaten '.($prefix === 'origin' ? 'asal' : 'tujuan').' pada daftar yang tersedia.');
                    }
                }
                if (blank($request->input('destination_city'))) {
                    $validator->errors()->add('destination_city', 'Kota / Kab tujuan wajib diisi.');
                }

                if (blank($request->input('regional_trip_scope'))) {
                    $validator->errors()->add('regional_trip_scope', 'Jenis perjalanan Sofifi wajib dipilih.');
                }
            } elseif (blank($request->input('destination_city'))) {
                $validator->errors()->add('destination_city', 'Kota / Kab tujuan wajib diisi.');
            } elseif (! in_array($request->input('destination_city'), $this->outsideRegionDestinationChoices(), true)) {
                $validator->errors()->add('destination_city', 'Kota / Kab tujuan luar daerah harus dipilih dari daftar acuan SBU.');
            }
        });

        $validated = $validator->validate();
        $isWithinRegion = $validated['category'] === 'Perjadin Dalam Daerah';
        $originRegency = $isWithinRegion ? ($validated['origin_regency'] ?? null) : null;
        $originDistrict = $isWithinRegion ? ($validated['origin_district'] ?? null) : null;
        $destinationRegency = $isWithinRegion ? ($validated['destination_regency'] ?? null) : null;
        $destinationDistrict = $isWithinRegion ? ($validated['destination_district'] ?? null) : null;
        $destinationCity = $validated['destination_city'] ?? null;
        $regionalTripScope = $isWithinRegion ? ($validated['regional_trip_scope'] ?? null) : null;
        $sofifiOver8Hours = $isWithinRegion && $regionalTripScope === 'dalam_kota_sofifi'
            ? (bool) ($validated['sofifi_over_8_hours'] ?? false)
            : false;

        $dailyAllowanceEnabled = (bool) ($validated['daily_allowance_enabled'] ?? false);
        $representationEnabled = (bool) ($validated['representation_enabled'] ?? false);
        $ticketEnabled = (bool) ($validated['ticket_enabled'] ?? false);
        $lodgingEnabled = (bool) ($validated['lodging_enabled'] ?? false);
        $lodgingHasReceipt = (bool) ($validated['lodging_has_receipt'] ?? false);
        $localTransportEnabled = (bool) ($validated['local_transport_enabled'] ?? false);
        $localTransportSegmentIds = $localTransportEnabled
            ? collect($validated['local_transport_segment_ids'] ?? [])
                ->filter(fn ($value) => filled($value))
                ->map(fn ($value) => (int) $value)
                ->unique()
                ->values()
                ->all()
            : [];
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
            'echelon_level' => $validated['echelon_level'],
            'grade' => $validated['grade'],
            'origin_regency' => $originRegency,
            'origin_district' => $originDistrict,
            'destination_regency' => $destinationRegency,
            'destination_district' => $destinationDistrict,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'assignment_number' => $validated['assignment_number'],
            'assignment_date' => $validated['assignment_date'],
            'signature_location' => $validated['signature_location'],
            'destination_city' => $destinationCity,
            'regional_trip_scope' => $regionalTripScope,
            'sofifi_over_8_hours' => $sofifiOver8Hours,

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
            'lodging_has_receipt' => $lodgingEnabled ? $lodgingHasReceipt : false,
            'lodging_nights' => $lodgingNights,
            'lodging_rate' => $lodgingRate,
            'lodging_total' => $lodgingTotal,
            'lodging_hotel_name' => $lodgingEnabled ? ($validated['lodging_hotel_name'] ?? null) : null,

            'local_transport_enabled' => $localTransportEnabled,
            'local_transport_segment_ids' => $localTransportSegmentIds,
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

        $originChoices = $this->localTransportOriginChoices();
        $regionChoices = $this->localTransportRegionChoices();

        return view('add-perjadin', [
            'title' => $title,
            'entry' => $entry,
            'currentPeriod' => $period,
            'periodLabel' => $period['label'].' '.$period['year'],
            'defaultStartDate' => sprintf('%04d-%02d-01', $period['year'], $period['month']),
            'activeCategory' => in_array($activeCategory, self::CATEGORY_OPTIONS, true) ? $activeCategory : '',
            'activeKeyword' => $activeKeyword,
            'categories' => self::CATEGORY_OPTIONS,
            'echelonOptions' => self::ECHELON_OPTIONS,
            'gradeOptions' => self::GRADE_OPTIONS,
            'transportTypes' => self::TRANSPORT_OPTIONS,
            'regencyOptions' => array_keys($regionChoices),
            'originDistrictOptions' => $originChoices,
            'destinationDistrictOptions' => $regionChoices,
            'localTransportReferences' => $this->localTransportReferences(),
            'airportTaxiReference' => $this->airportTaxiReference(),
            'flightTicketReferences' => $this->flightTicketReferences(),
            'outsideDestinationOptions' => $this->outsideRegionDestinationOptions(),
            'regionalLodgingReferences' => $this->regionalLodgingReferences(),
            'nationalLodgingReferences' => $this->nationalLodgingReferences(),
            'representationReferences' => $this->representationReferences(),
            'dailyAllowanceReferences' => $this->dailyAllowanceReferences(),
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
                    ['label' => 'Ada Nota', 'value' => $entry->lodging_has_receipt ? 'Ya, full SBU' : 'Tidak, lumpsum 30% dari SBU'],
                    ['label' => 'Nominal Dipakai', 'value' => $this->moneyLabel($entry->lodging_rate)],
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

    private function receiptBreakdown(PerjadinEntry $entry): array
    {
        $items = [];

        if ($entry->daily_allowance_enabled && $entry->daily_allowance_total > 0) {
            $items[] = [
                'description' => 'Uang Harian '.(int) $entry->daily_allowance_days.' hari x '.$this->moneyLabel($entry->daily_allowance_rate),
                'total' => (int) $entry->daily_allowance_total,
                'total_label' => $this->moneyLabel($entry->daily_allowance_total),
            ];
        }

        if ($entry->representation_enabled && $entry->representation_total > 0) {
            $items[] = [
                'description' => 'Uang Representasi '.(int) $entry->representation_days.' hari x '.$this->moneyLabel($entry->representation_rate),
                'total' => (int) $entry->representation_total,
                'total_label' => $this->moneyLabel($entry->representation_total),
            ];
        }

        if ($entry->ticket_enabled && $entry->ticket_total > 0) {
            $items[] = [
                'description' => 'Biaya Transportasi 1 orang x '.$this->moneyLabel($entry->ticket_total),
                'total' => (int) $entry->ticket_total,
                'total_label' => $this->moneyLabel($entry->ticket_total),
            ];
        }

        if ($entry->lodging_enabled && $entry->lodging_total > 0) {
            $items[] = [
                'description' => 'Biaya Penginapan '.(int) $entry->lodging_nights.' malam x '.$this->moneyLabel($entry->lodging_rate).($entry->lodging_has_receipt ? ' (full SBU / ada nota)' : ' (lumpsum 30% tanpa nota)'),
                'total' => (int) $entry->lodging_total,
                'total_label' => $this->moneyLabel($entry->lodging_total),
            ];
        }

        if ($entry->local_transport_enabled && $entry->local_transport_total > 0) {
            $items[] = [
                'description' => 'Transportasi Lokal 1 orang x '.$this->moneyLabel($entry->local_transport_total),
                'total' => (int) $entry->local_transport_total,
                'total_label' => $this->moneyLabel($entry->local_transport_total),
            ];
        }

        if ($entry->other_cost_enabled && $entry->other_cost_amount > 0) {
            $items[] = [
                'description' => 'Biaya Lain-lain 1 orang x '.$this->moneyLabel($entry->other_cost_amount),
                'total' => (int) $entry->other_cost_amount,
                'total_label' => $this->moneyLabel($entry->other_cost_amount),
            ];
        }

        return $items;
    }

    private function destinationCityLabel(string $regency, string $district): string
    {
        $parts = array_values(array_unique(array_filter([$district, $regency])));

        return implode(', ', $parts);
    }

    private function localTransportOriginChoices(): array
    {
        return [
            'Kota Ternate' => ['Sofifi', 'Ternate'],
            'Kota Tidore Kepulauan' => ['Sofifi', 'Tidore'],
            'Kabupaten Halmahera Tengah' => ['Sofifi', 'Weda'],
            'Kabupaten Halmahera Barat' => ['Sofifi', 'Ibu Kota Kabupaten'],
            'Kabupaten Halmahera Timur' => ['Sofifi', 'Ibu Kota Kabupaten'],
            'Kabupaten Pulau Morotai' => ['Sofifi', 'Ibu Kota Kabupaten'],
            'Kabupaten Halmahera Utara' => ['Sofifi', 'Tobelo'],
            'Kabupaten Halmahera Selatan' => ['Sofifi', 'Labuha', 'Labuha/Kupal', 'Labuha/Babang'],
            'Kabupaten Kepulauan Sula' => ['Sofifi', 'Sofifi/Ternate', 'Sula'],
            'Kabupaten Pulau Taliabu' => ['Sofifi/Ternate', 'Ibu Kota Kabupaten'],
        ];
    }
    private function localTransportRegionChoices(): array
    {
        $destinationGroups = LocalTransportSbu::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query
                    ->whereNull('area_name')
                    ->orWhere('area_name', 'not like', '%TAKSI BANDARA%');
            })
            ->get(['origin_regency', 'origin_label', 'destination_regency', 'destination_label'])
            ->reduce(function (array $carry, LocalTransportSbu $entry): array {
                foreach ([
                    [$entry->destination_regency, $entry->destination_label],
                    [$entry->origin_regency, $entry->origin_label],
                ] as [$regency, $label]) {
                    $regency = trim((string) $regency);
                    $label = trim((string) $label);

                    if ($regency === '' || $label === '') {
                        continue;
                    }

                    $carry[$regency] ??= [];
                    $carry[$regency][] = $label;
                }

                return $carry;
            }, []);

        $normalized = collect($destinationGroups)
            ->map(function (array $labels): array {
                return collect($labels)
                    ->filter()
                    ->unique()
                    ->sort(fn (string $left, string $right) => strcasecmp($left, $right))
                    ->values()
                    ->all();
            })
            ->all();

        uksort($normalized, fn (string $left, string $right) => strcasecmp($left, $right));

        return $normalized;
    }
    private function localTransportReferences(): array
    {
        return LocalTransportSbu::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query
                    ->whereNull('area_name')
                    ->orWhere('area_name', 'not like', '%TAKSI BANDARA%');
            })
            ->orderBy('sort_order')
            ->orderBy('area_name')
            ->orderBy('row_code')
            ->get([
                'id',
                'area_name',
                'row_code',
                'origin_regency',
                'origin_label',
                'destination_regency',
                'destination_label',
                'unit_label',
                'amount',
                'notes',
                'sort_order',
            ])
            ->map(fn (LocalTransportSbu $entry) => [
                'id' => $entry->id,
                'area_name' => $entry->area_name,
                'row_code' => $entry->row_code,
                'origin_regency' => $entry->origin_regency,
                'origin_label' => $entry->origin_label,
                'destination_regency' => $entry->destination_regency,
                'destination_label' => $entry->destination_label,
                'unit_label' => $entry->unit_label,
                'amount' => (int) $entry->amount,
                'amount_label' => $this->moneyLabel((int) $entry->amount),
                'notes' => $entry->notes,
                'sort_order' => (int) $entry->sort_order,
            ])
            ->values()
            ->all();
    }

    private function airportTaxiReference(): ?array
    {
        $entry = LocalTransportSbu::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query
                    ->where('row_code', 'like', 'TX-%')
                    ->orWhere('area_name', 'like', '%TAKSI BANDARA%');
            })
            ->orderBy('sort_order')
            ->first([
                'row_code',
                'origin_label',
                'destination_label',
                'amount',
                'notes',
            ]);

        if (! $entry) {
            return null;
        }

        return [
            'row_code' => $entry->row_code,
            'origin_label' => $entry->origin_label,
            'destination_label' => $entry->destination_label,
            'amount' => (int) $entry->amount,
            'amount_label' => $this->moneyLabel((int) $entry->amount),
            'notes' => $entry->notes,
        ];
    }

    private function flightTicketReferences(): array
    {
        return FlightTicketSbu::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('origin_city')
            ->orderBy('destination_city')
            ->get([
                'origin_city',
                'destination_city',
                'business_amount',
                'economy_amount',
            ])
            ->map(fn (FlightTicketSbu $entry) => [
                'origin_city' => $entry->origin_city,
                'destination_city' => $entry->destination_city,
                'business_amount' => (int) $entry->business_amount,
                'economy_amount' => (int) $entry->economy_amount,
            ])
            ->values()
            ->all();
    }

    private function outsideRegionDestinationOptions(): array
    {
        $provinceMap = TravelDestinationRegion::query()
            ->where('is_active', true)
            ->get(['city_name', 'province_name'])
            ->mapWithKeys(fn (TravelDestinationRegion $entry) => [
                strtoupper(trim($entry->city_name)) => trim($entry->province_name),
            ])
            ->all();

        return FlightTicketSbu::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('destination_city')
            ->get([
                'destination_city',
                'business_amount',
                'economy_amount',
            ])
            ->map(function (FlightTicketSbu $entry) use ($provinceMap): array {
                $destinationCity = trim((string) $entry->destination_city);
                $normalizedDestination = strtoupper($destinationCity);

                return [
                    'value' => $this->outsideRegionDestinationLabel($destinationCity),
                    'label' => $this->outsideRegionDestinationLabel($destinationCity),
                    'province_name' => $provinceMap[$normalizedDestination] ?? null,
                    'ticket_destination' => $destinationCity,
                    'business_amount' => (int) $entry->business_amount,
                    'economy_amount' => (int) $entry->economy_amount,
                ];
            })
            ->unique('value')
            ->values()
            ->all();
    }

    private function outsideRegionDestinationChoices(): array
    {
        return collect($this->outsideRegionDestinationOptions())
            ->pluck('value')
            ->values()
            ->all();
    }

    private function outsideRegionDestinationLabel(string $destinationCity): string
    {
        return match (strtoupper(trim($destinationCity))) {
            'LABUAN BAJO' => 'Labuan Bajo',
            default => ucwords(strtolower(trim($destinationCity))),
        };
    }

    private function regionalLodgingReferences(): array
    {
        return LodgingSbu::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('region_name')
            ->get([
                'region_name',
                'head_region_amount',
                'member_eselon_2_amount',
                'eselon_3_gol_4_amount',
                'eselon_4_gol_3_2_1_amount',
            ])
            ->map(fn (LodgingSbu $entry) => [
                'region_name' => $entry->region_name,
                'head_region_amount' => (int) $entry->head_region_amount,
                'member_eselon_2_amount' => (int) $entry->member_eselon_2_amount,
                'eselon_3_gol_4_amount' => (int) $entry->eselon_3_gol_4_amount,
                'eselon_4_gol_3_2_1_amount' => (int) $entry->eselon_4_gol_3_2_1_amount,
            ])
            ->values()
            ->all();
    }

    private function nationalLodgingReferences(): array
    {
        return NationalLodgingSbu::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('province_name')
            ->get([
                'province_name',
                'head_region_amount',
                'member_eselon_2_amount',
                'eselon_3_gol_4_amount',
                'eselon_4_gol_3_2_1_amount',
            ])
            ->map(fn (NationalLodgingSbu $entry) => [
                'province_name' => $entry->province_name,
                'head_region_amount' => (int) $entry->head_region_amount,
                'member_eselon_2_amount' => (int) $entry->member_eselon_2_amount,
                'eselon_3_gol_4_amount' => (int) $entry->eselon_3_gol_4_amount,
                'eselon_4_gol_3_2_1_amount' => (int) $entry->eselon_4_gol_3_2_1_amount,
            ])
            ->values()
            ->all();
    }

    private function representationReferences(): array
    {
        return RepresentationSbu::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get([
                'position_group',
                'outside_city_amount',
                'inside_city_over_8_hours_amount',
            ])
            ->map(fn (RepresentationSbu $entry) => [
                'position_group' => $entry->position_group,
                'outside_city_amount' => (int) $entry->outside_city_amount,
                'inside_city_over_8_hours_amount' => (int) $entry->inside_city_over_8_hours_amount,
            ])
            ->values()
            ->all();
    }

    private function dailyAllowanceReferences(): array
    {
        return DailyAllowanceSbu::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('province_name')
            ->get([
                'province_name',
                'outside_city_amount',
                'sofifi_inside_city_over_8_hours_amount',
                'diklat_amount',
            ])
            ->map(fn (DailyAllowanceSbu $entry) => [
                'province_name' => $entry->province_name,
                'outside_city_amount' => (int) $entry->outside_city_amount,
                'sofifi_inside_city_over_8_hours_amount' => (int) $entry->sofifi_inside_city_over_8_hours_amount,
                'diklat_amount' => (int) $entry->diklat_amount,
            ])
            ->values()
            ->all();
    }

    private function entrySbuComparisonSummary(PerjadinEntry $entry, array $context): ?array
    {
        $maximum = 0;
        $isRegional = $entry->category === 'Perjadin Dalam Daerah';

        if ($entry->daily_allowance_enabled && $entry->daily_allowance_days > 0) {
            $row = $this->dailyAllowanceReferenceForEntry($entry, $context['dailyAllowanceReferences']);
            $rate = $this->dailyAllowanceRateForEntry($entry, $row);

            $maximum += max(0, $rate) * (int) $entry->daily_allowance_days;
        }

        if ($entry->representation_enabled && $entry->representation_days > 0) {
            $row = $this->representationReferenceForEntry($entry, $context['representationReferences']);
            $rate = $this->representationRateForEntry($entry, $row);

            $maximum += max(0, $rate) * (int) $entry->representation_days;
        }

        if ($entry->ticket_enabled && ! $isRegional) {
            $row = $this->ticketReferenceForEntry($entry, $context['flightTicketReferences'], $context['destinationOptions']);
            $maximum += $row ? (int) ($row['economy_amount'] ?: $row['business_amount'] ?: 0) : 0;
        }

        if ($entry->lodging_enabled && $entry->lodging_nights > 0) {
            $row = $this->lodgingReferenceForEntry($entry, $context['regionalLodgingReferences'], $context['nationalLodgingReferences'], $context['destinationOptions']);
            $rate = $this->lodgingComparisonRateForEntry($entry, $row);
            $maximum += max(0, $rate) * (int) $entry->lodging_nights;
        }

        if ($isRegional) {
            $maximum += $this->regionalLocalTransportMaximumForEntry($entry, $context['localTransportReferences'] ?? []);
        } elseif ($entry->local_transport_enabled) {
            $maximum += (int) ($context['airportTaxiReference']['amount'] ?? 0);
        }

        if ($maximum <= 0) {
            return null;
        }

        $actual = (int) $entry->grand_total;
        $difference = $actual - $maximum;

        if ($difference > 0) {
            return [
                'status' => 'over',
                'label' => 'Lebih dari SBU',
                'tone' => 'border-rose-200 bg-rose-50 text-rose-700',
            ];
        }

        if ($difference < 0) {
            return [
                'status' => 'under',
                'label' => 'Kurang dari SBU',
                'tone' => 'border-amber-200 bg-amber-50 text-amber-700',
            ];
        }

        return [
            'status' => 'match',
            'label' => 'Pas sesuai SBU',
            'tone' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        ];
    }

    private function dailyAllowanceReferenceForEntry(PerjadinEntry $entry, array $references): ?array
    {
        if ($entry->category === 'Perjadin Dalam Daerah') {
            return $this->findReferenceByNormalizedValue($references, 'province_name', 'Maluku Utara');
        }

        $provinceName = $this->outsideProvinceNameForEntry($entry);
        if (! $provinceName) {
            return null;
        }

        return $this->findReferenceByNormalizedValue($references, 'province_name', $provinceName);
    }

    private function dailyAllowanceRateForEntry(PerjadinEntry $entry, ?array $row): int
    {
        if (! $row) {
            return 0;
        }

        if ($entry->category === 'Perjadin Dalam Daerah') {
            if ($entry->regional_trip_scope === 'luar_kota_sofifi') {
                return (int) ($row['outside_city_amount'] ?? 0);
            }

            if ($entry->regional_trip_scope === 'dalam_kota_sofifi') {
                return $entry->sofifi_over_8_hours
                    ? (int) ($row['sofifi_inside_city_over_8_hours_amount'] ?? 0)
                    : 0;
            }

            return 0;
        }

        return (int) ($row['outside_city_amount'] ?? 0);
    }

    private function representationReferenceForEntry(PerjadinEntry $entry, array $references): ?array
    {
        $key = match ((string) $entry->echelon_level) {
            '1' => 'PEJABAT ESELON I',
            '2' => 'PEJABAT ESELON II',
            default => null,
        };

        if (! $key) {
            return null;
        }

        return $this->findReferenceByNormalizedValue($references, 'position_group', $key);
    }

    private function representationRateForEntry(PerjadinEntry $entry, ?array $row): int
    {
        if (! $row) {
            return 0;
        }

        if ($entry->category === 'Perjadin Dalam Daerah') {
            if ($entry->regional_trip_scope === 'luar_kota_sofifi') {
                return (int) ($row['outside_city_amount'] ?? 0);
            }

            if ($entry->regional_trip_scope === 'dalam_kota_sofifi') {
                return $entry->sofifi_over_8_hours
                    ? (int) ($row['inside_city_over_8_hours_amount'] ?? 0)
                    : 0;
            }

            return 0;
        }

        return (int) ($row['outside_city_amount'] ?? 0);
    }

    private function ticketReferenceForEntry(PerjadinEntry $entry, array $references, array $destinationOptions): ?array
    {
        $destination = $this->outsideDestinationOptionForEntry($entry, $destinationOptions);
        if (! $destination) {
            return null;
        }

        return collect($references)->first(function (array $row) use ($destination): bool {
            return $this->normalizeLookupText($row['origin_city'] ?? '') === 'ternate'
                && $this->normalizeLookupText($row['destination_city'] ?? '') === $this->normalizeLookupText($destination['ticket_destination'] ?? '');
        });
    }

    private function lodgingReferenceForEntry(PerjadinEntry $entry, array $regionalReferences, array $nationalReferences, array $destinationOptions): ?array
    {
        if ($entry->category === 'Perjadin Dalam Daerah') {
            $candidates = array_filter([
                $entry->destination_regency,
                $entry->destination_city,
            ]);

            foreach ($candidates as $candidate) {
                $found = $this->findReferenceByNormalizedValue($regionalReferences, 'region_name', (string) $candidate);
                if ($found) {
                    return $found;
                }
            }

            return null;
        }

        $provinceName = $this->outsideProvinceNameForEntry($entry, $destinationOptions);
        if (! $provinceName) {
            return null;
        }

        return $this->findReferenceByNormalizedValue($nationalReferences, 'province_name', $provinceName);
    }

    private function lodgingComparisonRateForEntry(PerjadinEntry $entry, ?array $row): int
    {
        if (! $row) {
            return 0;
        }

        $baseRate = match ((string) $entry->echelon_level) {
            '1' => (int) ($row['head_region_amount'] ?? 0),
            '2' => (int) ($row['member_eselon_2_amount'] ?? 0),
            '3' => (int) ($row['eselon_3_gol_4_amount'] ?? 0),
            '4' => (int) ($row['eselon_4_gol_3_2_1_amount'] ?? 0),
            default => 0,
        };

        if ($baseRate <= 0) {
            return 0;
        }

        return $entry->lodging_has_receipt ? $baseRate : (int) round($baseRate * 0.3);
    }

    private function outsideDestinationOptionForEntry(PerjadinEntry $entry, ?array $destinationOptions = null): ?array
    {
        $options = $destinationOptions ?? $this->outsideRegionDestinationOptions();
        $destinationCity = $entry->destination_city ?? '';

        return collect($options)->first(function (array $option) use ($destinationCity): bool {
            return $this->normalizeLookupText($option['value'] ?? '') === $this->normalizeLookupText($destinationCity);
        });
    }

    private function outsideProvinceNameForEntry(PerjadinEntry $entry, ?array $destinationOptions = null): ?string
    {
        return $this->outsideDestinationOptionForEntry($entry, $destinationOptions)['province_name'] ?? null;
    }

    private function findReferenceByNormalizedValue(array $rows, string $key, string $target): ?array
    {
        return collect($rows)->first(function (array $row) use ($key, $target): bool {
            return $this->normalizeLookupText($row[$key] ?? '') === $this->normalizeLookupText($target);
        });
    }

    private function regionalLocalTransportMaximumForEntry(PerjadinEntry $entry, array $references): int
    {
        $ids = collect($entry->local_transport_segment_ids ?? [])
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => (int) $value)
            ->all();

        if ($ids === []) {
            return 0;
        }

        return collect($references)
            ->whereIn('id', $ids)
            ->sum(fn (array $row) => (int) ($row['amount'] ?? 0));
    }

    private function normalizeLookupText(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? '';

        return trim($value);
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



















