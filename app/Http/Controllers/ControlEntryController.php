<?php

namespace App\Http\Controllers;

use App\Models\ControlEntry;
use App\Models\ControlEntrySettlement;
use App\Models\SavingAllocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ControlEntryController extends Controller
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

    private const BASE_FUND_SOURCES = [
        'YULIA',
        'VIVI',
        'TERAS ATIQAH',
    ];

    private const SAVING_FUND_SOURCES = [
        'DANSAV MAKMUR',
        'DANSAV UPY',
        'DANSAV TERAS ATIQAH',
        'DANSAV GU',
    ];

    private const TRANSACTION_TYPES = [
        'operasional_langsung' => 'Operasional Dibayar Langsung',
    ];

    private const STATUSES = [
        'LUNAS',
        'HUTANG',
        'BAYAR SEBAGIAN',
        'MASUK',
    ];

    private const MANUAL_STATUS_SOURCES = [
        'YULIA',
        'VIVI',
    ];

    private const HANDOVER_MOMENTS = [
        'Pagi',
        'Siang',
        'Sore',
        'Malam',
        '-',
    ];

    public function index(Request $request): View
    {
        $period = $this->selectedPeriod($request);

        $baseQuery = ControlEntry::query()
            ->with(['debtSettlements', 'savingSettlements', 'savingAllocationSettlements'])
            ->whereIn('transaction_type', array_keys(self::TRANSACTION_TYPES))
            ->whereYear('entry_date', $period['year'])
            ->whereMonth('entry_date', $period['month']);

        $fundSourceOptions = (clone $baseQuery)
            ->select('fund_source')
            ->whereNotNull('fund_source')
            ->distinct()
            ->orderBy('fund_source')
            ->pluck('fund_source')
            ->values()
            ->all();

        $selectedFundSource = $request->string('fund_source')->toString();

        if ($selectedFundSource !== '' && in_array($selectedFundSource, $fundSourceOptions, true)) {
            $baseQuery->where('fund_source', $selectedFundSource);
        } else {
            $selectedFundSource = '';
        }

        $entries = $baseQuery
            ->latest('entry_date')
            ->latest()
            ->get();

        $operationalEntries = $entries->where('transaction_type', 'operasional_langsung');
        return view('lembar-kontrol', [
            'title' => 'Lembar Kontrol',
            'entries' => $entries,
            'periodLabel' => $period['label'].' '.$period['year'],
            'currentPeriod' => $period,
            'monthOptions' => $this->monthOptions(),
            'yearOptions' => $this->yearOptions(),
            'fundSourceOptions' => $fundSourceOptions,
            'selectedFundSource' => $selectedFundSource,
            'summary' => [
                'totalCount' => $entries->count(),
                'operationalTotal' => (int) $operationalEntries->sum('obligation_amount'),
                'savingInflowTotal' => 0,
                'activeDebtTotal' => 0,
                'pendingCount' => 0,
                'settledDebtTotal' => 0,
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return $this->formView('Tambah Data Kontrol', null, $this->selectedPeriod($request));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $proof = $this->storeProofFile($request);

        DB::transaction(function () use ($data, $proof, $request) {
            ControlEntry::create($this->payload($data, [
                'proof_path' => $proof['path'],
                'proof_original_name' => $proof['name'],
                'created_by' => $request->user()->id,
            ]));

            $this->rebuildAutoSettlements();
            $this->refreshTransactionStatuses();
        });

        return redirect()->route('lembar-kontrol', [
            'month' => (int) date('n', strtotime($data['entry_date'])),
            'year' => (int) date('Y', strtotime($data['entry_date'])),
        ])->with('status', 'Data kontrol berhasil disimpan ke database.');
    }

    public function edit(ControlEntry $controlEntry): View
    {
        return $this->formView(
            'Edit Data Kontrol',
            $controlEntry->load(['debtSettlements', 'savingSettlements'])
        );
    }

    public function showProof(ControlEntry $controlEntry)
    {
        if (! $controlEntry->proof_path || ! Storage::disk('public')->exists($controlEntry->proof_path)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('public')->path($controlEntry->proof_path),
            [
                'Content-Type' => Storage::disk('public')->mimeType($controlEntry->proof_path) ?: 'application/octet-stream',
            ]
        );
    }

    public function update(Request $request, ControlEntry $controlEntry): RedirectResponse
    {
        $data = $this->validatedData($request);
        $proof = $this->storeProofFile($request);
        $oldProofPath = $controlEntry->proof_path;

        DB::transaction(function () use ($data, $proof, $request, $controlEntry) {
            $controlEntry->update($this->payload($data, [
                'proof_path' => $proof['path'] ?? $controlEntry->proof_path,
                'proof_original_name' => $proof['name'] ?? $controlEntry->proof_original_name,
                'created_by' => $controlEntry->created_by ?? $request->user()->id,
            ]));

            $this->rebuildAutoSettlements();
            $this->refreshTransactionStatuses();
        });

        if ($proof['path'] && $oldProofPath) {
            Storage::disk('public')->delete($oldProofPath);
        }

        return redirect()->route('lembar-kontrol', [
            'month' => (int) date('n', strtotime($data['entry_date'])),
            'year' => (int) date('Y', strtotime($data['entry_date'])),
        ])->with('status', 'Data kontrol berhasil diperbarui.');
    }

    public function destroy(ControlEntry $controlEntry): RedirectResponse
    {
        $month = optional($controlEntry->entry_date)->month ?? now()->month;
        $year = optional($controlEntry->entry_date)->year ?? now()->year;
        $proofPath = $controlEntry->proof_path;

        DB::transaction(function () use ($controlEntry) {
            $controlEntry->delete();

            $this->rebuildAutoSettlements();
            $this->refreshTransactionStatuses();
        });

        if ($proofPath) {
            Storage::disk('public')->delete($proofPath);
        }

        return redirect()->route('lembar-kontrol', [
            'month' => $month,
            'year' => $year,
        ])->with('status', 'Data kontrol berhasil dihapus.');
    }

    public function duplicate(Request $request, ControlEntry $controlEntry): RedirectResponse
    {
        $duplicate = null;

        DB::transaction(function () use ($controlEntry, $request, &$duplicate) {
            $duplicate = $controlEntry->replicate([
                'proof_path',
                'proof_original_name',
                'created_at',
                'updated_at',
            ]);

            $duplicate->fill([
                'proof_path' => $this->duplicateProofFile($controlEntry->proof_path),
                'proof_original_name' => $controlEntry->proof_original_name,
                'created_by' => $request->user()->id,
            ]);

            $duplicate->save();

            $this->rebuildAutoSettlements();
            $this->refreshTransactionStatuses();
        });

        return redirect()
            ->route('lembar-kontrol.edit', $duplicate)
            ->with('status', 'Data kontrol berhasil diduplikat. Silakan lanjut edit data hasil duplikat.');
    }

    public function destroyPeriod(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'integer', Rule::in(array_keys(self::PERIOD_MONTHS))],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        $entries = ControlEntry::query()
            ->whereYear('entry_date', $validated['year'])
            ->whereMonth('entry_date', $validated['month'])
            ->get();

        if ($entries->isEmpty()) {
            return redirect()->route('lembar-kontrol', [
                'month' => $validated['month'],
                'year' => $validated['year'],
            ])->with('status', 'Tidak ada data lembar kontrol pada periode ini yang perlu dihapus.');
        }

        $proofPaths = $entries
            ->pluck('proof_path')
            ->filter()
            ->values();

        $deletedCount = $entries->count();

        DB::transaction(function () use ($validated) {
            ControlEntry::query()
                ->whereYear('entry_date', $validated['year'])
                ->whereMonth('entry_date', $validated['month'])
                ->delete();

            $this->rebuildAutoSettlements();
            $this->refreshTransactionStatuses();
        });

        foreach ($proofPaths as $proofPath) {
            Storage::disk('public')->delete($proofPath);
        }

        return redirect()->route('lembar-kontrol', [
            'month' => $validated['month'],
            'year' => $validated['year'],
        ])->with('status', $deletedCount.' data lembar kontrol untuk '.$this->periodLabel($validated['month'], $validated['year']).' berhasil dihapus.');
    }

    private function formView(string $title, ?ControlEntry $entry = null, ?array $period = null): View
    {
        $period ??= $entry
            ? [
                'month' => optional($entry->entry_date)->month ?? 2,
                'year' => optional($entry->entry_date)->year ?? now()->year,
                'label' => self::PERIOD_MONTHS[optional($entry->entry_date)->month ?? 2] ?? self::PERIOD_MONTHS[2],
            ]
            : [
                'month' => now()->month,
                'year' => now()->year,
                'label' => self::PERIOD_MONTHS[now()->month],
            ];

        return view('add-data-kontrol', [
            'title' => $title,
            'entry' => $entry,
            'currentPeriod' => $period,
            'periodLabel' => $period['label'].' '.$period['year'],
            'defaultEntryDate' => sprintf('%04d-%02d-01', $period['year'], $period['month']),
            'sources' => $this->availableFundSources(),
            'directSources' => $this->directFundSources(),
            'manualStatusSources' => self::MANUAL_STATUS_SOURCES,
            'handoverMoments' => self::HANDOVER_MOMENTS,
        ]);
    }

    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'handover_time' => ['required', 'string', Rule::in(self::HANDOVER_MOMENTS)],
            'amount_out' => ['nullable', 'integer', 'min:0'],
            'third_party' => ['nullable', 'string', 'max:255'],
            'receiving_officer' => ['required', 'string', 'max:255'],
            'appointed_official' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'purpose' => ['required', 'string'],
            'fund_source' => ['required', 'string'],
            'status' => ['nullable', 'string'],
            'proof_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $transactionType = 'operasional_langsung';
        $amountOut = (int) ($validated['amount_out'] ?? 0);
        $fundSource = $validated['fund_source'];

        if ($transactionType === 'operasional_langsung' && $amountOut <= 0) {
            throw ValidationException::withMessages([
                'amount_out' => 'Nominal operasional wajib diisi untuk transaksi operasional.',
            ]);
        }

        $allowedSources = $this->allowedSourcesForType($transactionType);

        if (! in_array($fundSource, $allowedSources, true)) {
            throw ValidationException::withMessages([
                'fund_source' => 'Sumber dana tidak sesuai dengan jenis transaksi yang dipilih.',
            ]);
        }

        if (in_array($fundSource, self::MANUAL_STATUS_SOURCES, true)) {
            if (! in_array($validated['status'] ?? null, ['LUNAS', 'HUTANG'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Status untuk sumber dana YULIA atau VIVI wajib dipilih antara LUNAS atau HUTANG.',
                ]);
            }
        } else {
            $validated['status'] = 'LUNAS';
        }

        $validated['amount_out'] = $amountOut;
        $validated['auto_settle_open_debts'] = false;
        $validated['transaction_type'] = $transactionType;

        return $validated;
    }

    private function storeProofFile(Request $request): array
    {
        if (! $request->hasFile('proof_file')) {
            return [
                'path' => null,
                'name' => null,
            ];
        }

        return [
            'path' => $request->file('proof_file')->store('proofs/control', 'public'),
            'name' => $request->file('proof_file')->getClientOriginalName(),
        ];
    }

    private function duplicateProofFile(?string $proofPath): ?string
    {
        if (! $proofPath || ! Storage::disk('public')->exists($proofPath)) {
            return null;
        }

        $extension = pathinfo($proofPath, PATHINFO_EXTENSION);
        $duplicatePath = 'proofs/control/'.uniqid('copy_', true).($extension ? '.'.$extension : '');

        Storage::disk('public')->copy($proofPath, $duplicatePath);

        return $duplicatePath;
    }

    private function payload(array $data, array $extra = []): array
    {
        $payload = [
            'entry_date' => $data['entry_date'],
            'handover_time' => $data['handover_time'],
            'transaction_type' => $data['transaction_type'],
            'third_party' => $data['third_party'] ?? null,
            'receiving_officer' => $data['receiving_officer'],
            'appointed_official' => $data['appointed_official'],
            'location' => $data['location'],
            'purpose' => $data['purpose'],
            'fund_source' => $data['fund_source'],
            'financier_name' => null,
            'auto_settle_open_debts' => false,
            'amount_out' => 0,
            'amount_in' => 0,
            'obligation_amount' => 0,
            'status' => 'LUNAS',
            'partial_payment_amount' => 0,
        ];

        if ($data['transaction_type'] === 'operasional_langsung') {
            $payload['amount_out'] = $data['amount_out'];
            $payload['obligation_amount'] = $data['amount_out'];
            $payload['status'] = $data['status'] ?? 'LUNAS';
        }

        return array_merge($payload, $extra);
    }

    private function rebuildAutoSettlements(): void
    {
        ControlEntrySettlement::query()->delete();

        $debts = $this->openDebtEntries();
        $settledProgress = $debts
            ->mapWithKeys(fn (ControlEntry $entry) => [$entry->id => (int) $entry->partial_payment_amount])
            ->all();

        $savingInflows = ControlEntry::query()
            ->where('transaction_type', 'saving_masuk')
            ->where('auto_settle_open_debts', true)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        foreach ($savingInflows as $savingEntry) {
            $remainingInflow = (int) $savingEntry->amount_in;

            foreach ($debts as $debtEntry) {
                if (! $this->isSamePeriodDebt($debtEntry, $savingEntry)) {
                    continue;
                }

                $remainingDebt = max(
                    (int) $debtEntry->obligation_amount - (int) ($settledProgress[$debtEntry->id] ?? 0),
                    0
                );

                if ($remainingDebt <= 0) {
                    continue;
                }

                $settlementAmount = min($remainingInflow, $remainingDebt);

                if ($settlementAmount <= 0) {
                    continue;
                }

                ControlEntrySettlement::create([
                    'saving_inflow_entry_id' => $savingEntry->id,
                    'debt_entry_id' => $debtEntry->id,
                    'amount' => $settlementAmount,
                    'settlement_date' => $savingEntry->entry_date,
                    'created_by' => $savingEntry->created_by,
                ]);

                $settledProgress[$debtEntry->id] = (int) ($settledProgress[$debtEntry->id] ?? 0) + $settlementAmount;
                $remainingInflow -= $settlementAmount;

                if ($remainingInflow <= 0) {
                    break;
                }
            }
        }
    }

    private function refreshTransactionStatuses(): void
    {
        ControlEntry::query()
            ->with('debtSettlements')
            ->get()
            ->each(function (ControlEntry $entry) {
                if ($entry->transaction_type === 'saving_masuk') {
                    $entry->forceFill(['status' => 'MASUK'])->saveQuietly();

                    return;
                }

                if ($entry->transaction_type === 'operasional_langsung') {
                    $entry->forceFill([
                        'status' => in_array($entry->fund_source, self::MANUAL_STATUS_SOURCES, true)
                            ? ($entry->status === 'HUTANG' ? 'HUTANG' : 'LUNAS')
                            : 'LUNAS',
                    ])->saveQuietly();

                    return;
                }

                $settledAmount = (int) $entry->partial_payment_amount + (int) $entry->debtSettlements->sum('amount');
                $remainingDebt = max((int) $entry->obligation_amount - $settledAmount, 0);

                $entry->forceFill([
                    'status' => $remainingDebt <= 0
                        ? 'LUNAS'
                        : ($settledAmount > 0 ? 'BAYAR SEBAGIAN' : 'HUTANG'),
                ])->saveQuietly();
            });
    }

    private function openDebtEntries()
    {
        return ControlEntry::query()
            ->with(['debtSettlements', 'savingAllocationSettlements'])
            ->where('transaction_type', 'operasional_talangan')
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();
    }

    private function isSamePeriodDebt(ControlEntry $debtEntry, ControlEntry $savingEntry): bool
    {
        return $debtEntry->entry_date->year === $savingEntry->entry_date->year
            && $debtEntry->entry_date->month === $savingEntry->entry_date->month;
    }

    private function availableFundSources(): array
    {
        return $this->directFundSources();
    }

    private function directFundSources(): array
    {
        return collect(self::BASE_FUND_SOURCES)
            ->merge($this->savingFundSources())
            ->unique()
            ->values()
            ->all();
    }

    private function savingFundSources(): array
    {
        $savingSources = SavingAllocation::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('source_name')
            ->all();

        return collect(self::SAVING_FUND_SOURCES)
            ->merge($savingSources)
            ->unique()
            ->values()
            ->all();
    }

    private function allowedSourcesForType(string $transactionType): array
    {
        return match ($transactionType) {
            'operasional_langsung' => $this->directFundSources(),
            default => [],
        };
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

    private function periodLabel(int $month, int $year): string
    {
        return (self::PERIOD_MONTHS[$month] ?? 'Periode').' '.$year;
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
}
