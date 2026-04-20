<?php

namespace App\Http\Controllers;

use App\Models\ControlEntry;
use App\Models\SavingAllocation;
use App\Models\SavingAllocationDebtSettlement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SavingAllocationController extends Controller
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

    private const SOURCE_OPTIONS = [
        'DANSAV MAKMUR',
        'DANSAV UPY',
        'DANSAV TERAS ATIQAH',
        'DANSAV GU',
    ];

    public function index(Request $request): View
    {
        $period = $this->selectedPeriod($request);

        $allocations = SavingAllocation::query()
            ->with('debtSettlements')
            ->where('period_month', $period['month'])
            ->where('period_year', $period['year'])
            ->orderBy('sort_order')
            ->orderBy('source_name')
            ->orderBy('created_at')
            ->get();

        $groupedSummary = $allocations
            ->where('is_active', true)
            ->groupBy('source_name')
            ->map(function (Collection $items, string $source) {
                return [
                    'source' => $source,
                    'total' => (int) $items->sum('amount'),
                    'entries' => $items->count(),
                    'settled' => (int) $items->sum(fn (SavingAllocation $allocation) => $allocation->settledAmount()),
                ];
            })
            ->sortBy('source')
            ->values();

        $outstandingDebt = $this->periodDebtEntries($period['month'], $period['year'])
            ->sum(fn (ControlEntry $entry) => $entry->remainingDebt());

        return view('saving-allocations.index', [
            'title' => 'Dana Saving',
            'allocations' => $allocations,
            'groupedSummary' => $groupedSummary,
            'periodLabel' => $period['label'].' '.$period['year'],
            'currentPeriod' => $period,
            'monthOptions' => $this->monthOptions(),
            'yearOptions' => $this->yearOptions(),
            'summary' => [
                'count' => $allocations->count(),
                'total' => (int) $allocations->where('is_active', true)->sum('amount'),
                'active' => $allocations->where('is_active', true)->count(),
                'activeSources' => $groupedSummary->count(),
                'outstandingDebt' => (int) $outstandingDebt,
                'settledDebt' => (int) $allocations->sum(fn (SavingAllocation $allocation) => $allocation->settledAmount()),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $period = $this->selectedPeriod($request);

        return $this->formView('Tambah Dana Saving', null, $period);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        DB::transaction(function () use ($data) {
            SavingAllocation::query()->create($data);
            $this->rebuildSavingSettlementsForPeriod($data['period_month'], $data['period_year']);
        });

        return redirect()->route('dana-saving.index', [
            'month' => $data['period_month'],
            'year' => $data['period_year'],
        ])->with('status', 'Dana saving berhasil ditambahkan.');
    }

    public function edit(SavingAllocation $savingAllocation): View
    {
        $period = [
            'month' => $savingAllocation->period_month,
            'year' => $savingAllocation->period_year,
            'label' => self::PERIOD_MONTHS[$savingAllocation->period_month] ?? 'Periode',
        ];

        return $this->formView('Edit Dana Saving', $savingAllocation->load('debtSettlements'), $period);
    }

    public function update(Request $request, SavingAllocation $savingAllocation): RedirectResponse
    {
        $data = $this->validatedData($request, $savingAllocation);
        $oldMonth = $savingAllocation->period_month;
        $oldYear = $savingAllocation->period_year;

        DB::transaction(function () use ($data, $savingAllocation, $oldMonth, $oldYear) {
            $periodChanged = $oldMonth !== (int) $data['period_month'] || $oldYear !== (int) $data['period_year'];
            if ($periodChanged) {
                SavingAllocationDebtSettlement::query()
                    ->where('saving_allocation_id', $savingAllocation->id)
                    ->delete();
            }

            $savingAllocation->update($data);
            if ($periodChanged) {
                $this->rebuildSavingSettlementsForPeriod($oldMonth, $oldYear);
            }
            $this->rebuildSavingSettlementsForPeriod($data['period_month'], $data['period_year']);
        });

        return redirect()->route('dana-saving.index', [
            'month' => $data['period_month'],
            'year' => $data['period_year'],
        ])->with('status', 'Dana saving berhasil diperbarui.');
    }

    public function destroy(SavingAllocation $savingAllocation): RedirectResponse
    {
        $month = $savingAllocation->period_month;
        $year = $savingAllocation->period_year;

        DB::transaction(function () use ($savingAllocation, $month, $year) {
            $savingAllocation->delete();
            $this->rebuildSavingSettlementsForPeriod($month, $year);
        });

        return redirect()->route('dana-saving.index', [
            'month' => $month,
            'year' => $year,
        ])->with('status', 'Dana saving berhasil dihapus.');
    }

    public function settleDebts(SavingAllocation $savingAllocation): RedirectResponse
    {
        $month = $savingAllocation->period_month;
        $year = $savingAllocation->period_year;

        DB::transaction(function () use ($savingAllocation, $month, $year) {
            $savingAllocation->forceFill(['auto_settle_debts' => true])->save();
            $this->rebuildSavingSettlementsForPeriod($month, $year);
        });

        return redirect()->route('dana-saving.index', [
            'month' => $month,
            'year' => $year,
        ])->with('status', 'Dana saving dipakai untuk melunasi hutang pada periode yang sama.');
    }

    private function formView(string $title, ?SavingAllocation $allocation = null, ?array $period = null): View
    {
        $period ??= [
            'month' => now()->month,
            'year' => now()->year,
            'label' => self::PERIOD_MONTHS[now()->month],
        ];

        $outstandingDebt = $this->periodDebtEntries($period['month'], $period['year'])
            ->sum(fn (ControlEntry $entry) => $entry->remainingDebt());

        return view('saving-allocations.form', [
            'title' => $title,
            'allocation' => $allocation,
            'periodMonth' => $period['month'],
            'periodYear' => $period['year'],
            'periodLabel' => $period['label'].' '.$period['year'],
            'sourceOptions' => self::SOURCE_OPTIONS,
            'outstandingDebt' => (int) $outstandingDebt,
        ]);
    }

    private function validatedData(Request $request, ?SavingAllocation $allocation = null): array
    {
        $validated = $request->validate([
            'period_month' => ['required', 'integer', Rule::in(array_keys(self::PERIOD_MONTHS))],
            'period_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'source_name' => [
                'required',
                'string',
                'max:255',
                Rule::in(self::SOURCE_OPTIONS),
            ],
            'amount' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'auto_settle_debts' => ['nullable', 'boolean'],
        ], [
            'source_name.in' => 'Sumber dana saving harus dipilih dari daftar yang tersedia.',
        ]);

        return array_merge($validated, [
            'source_name' => strtoupper((string) $request->input('source_name')),
            'sort_order' => $this->sortOrderForSource((string) $request->input('source_name')),
            'is_active' => $request->boolean('is_active'),
            'auto_settle_debts' => $request->boolean('auto_settle_debts'),
        ]);
    }

    private function rebuildSavingSettlementsForPeriod(int $month, int $year): void
    {
        $allocationIds = SavingAllocation::query()
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->pluck('id');

        if ($allocationIds->isEmpty()) {
            $this->refreshPeriodDebtStatuses($month, $year);

            return;
        }

        SavingAllocationDebtSettlement::query()
            ->whereIn('saving_allocation_id', $allocationIds)
            ->delete();

        $debts = $this->periodDebtEntries($month, $year);
        $settledProgress = $debts
            ->mapWithKeys(function (ControlEntry $entry) {
                return [$entry->id => (int) $entry->partial_payment_amount + (int) $entry->debtSettlements->sum('amount')];
            })
            ->all();

        $allocations = SavingAllocation::query()
            ->with('debtSettlements')
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->where('is_active', true)
            ->where('auto_settle_debts', true)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($allocations as $allocation) {
            $remainingAllocation = (int) $allocation->amount;

            foreach ($debts as $debtEntry) {
                $remainingDebt = max(
                    (int) $debtEntry->obligation_amount - (int) ($settledProgress[$debtEntry->id] ?? 0),
                    0
                );

                if ($remainingDebt <= 0 || $remainingAllocation <= 0) {
                    continue;
                }

                $settlementAmount = min($remainingAllocation, $remainingDebt);

                SavingAllocationDebtSettlement::query()->create([
                    'saving_allocation_id' => $allocation->id,
                    'debt_entry_id' => $debtEntry->id,
                    'amount' => $settlementAmount,
                ]);

                $settledProgress[$debtEntry->id] = (int) ($settledProgress[$debtEntry->id] ?? 0) + $settlementAmount;
                $remainingAllocation -= $settlementAmount;

                if ($remainingAllocation <= 0) {
                    break;
                }
            }
        }

        $this->refreshPeriodDebtStatuses($month, $year);
    }

    private function refreshPeriodDebtStatuses(int $month, int $year): void
    {
        $this->periodDebtEntries($month, $year)->each(function (ControlEntry $entry) {
            $remainingDebt = $entry->remainingDebt();
            $settledAmount = $entry->settledAmount();

            $entry->forceFill([
                'status' => $remainingDebt <= 0
                    ? 'LUNAS'
                    : ($settledAmount > 0 ? 'BAYAR SEBAGIAN' : 'HUTANG'),
            ])->saveQuietly();
        });
    }

    private function periodDebtEntries(int $month, int $year)
    {
        return ControlEntry::query()
            ->with(['debtSettlements', 'savingAllocationSettlements'])
            ->where('transaction_type', 'operasional_talangan')
            ->whereYear('entry_date', $year)
            ->whereMonth('entry_date', $month)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();
    }

    private function sortOrderForSource(string $sourceName): int
    {
        $index = array_search(strtoupper($sourceName), self::SOURCE_OPTIONS, true);

        return $index === false ? count(self::SOURCE_OPTIONS) + 1 : $index + 1;
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
}
