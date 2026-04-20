<?php

namespace App\Http\Controllers;

use App\Models\ControlEntry;
use App\Models\SavingAllocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
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

    private const NON_SAVING_SOURCE_ORDER = [
        'YULIA',
        'VIVI',
        'TERAS ATIQAH',
        'TALANGAN BENDAHARA',
        'TALANGAN KARYAWAN',
    ];

    public function index(Request $request): View|RedirectResponse
    {
        if (auth()->user()->role === 'verifikator') {
            return redirect()->route('perjadin');
        }

        $period = $this->selectedPeriod($request);

        $entries = ControlEntry::query()
            ->with(['debtSettlements', 'savingSettlements', 'savingAllocationSettlements.savingAllocation'])
            ->whereYear('entry_date', $period['year'])
            ->whereMonth('entry_date', $period['month'])
            ->latest('entry_date')
            ->latest()
            ->get();

        $operationalEntries = $entries->whereIn('transaction_type', [
            'operasional_langsung',
            'operasional_talangan',
        ]);

        $talanganEntries = $entries->where('transaction_type', 'operasional_talangan');

        $savingAllocations = SavingAllocation::query()
            ->where('period_month', $period['month'])
            ->where('period_year', $period['year'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('source_name')
            ->orderBy('created_at')
            ->get();

        $groupedSavingAllocations = $savingAllocations
            ->groupBy('source_name')
            ->map(function (Collection $items, string $source) {
                return [
                    'source' => $source,
                    'amount' => (int) $items->sum('amount'),
                    'sort_order' => (int) $items->min('sort_order'),
                    'entries_count' => $items->count(),
                ];
            })
            ->sortBy([
                ['sort_order', 'asc'],
                ['source', 'asc'],
            ])
            ->values();

        $tableOne = $this->buildTableOne($operationalEntries, $groupedSavingAllocations->pluck('source'));
        $tableOneTotals = [
            'hutang' => (int) $tableOne->sum('hutang'),
            'lunas' => (int) $tableOne->sum('lunas'),
            'partial' => (int) $tableOne->sum('partial'),
            'total' => (int) $tableOne->sum('total'),
        ];

        $tableTwoOne = $groupedSavingAllocations;

        $directSavingUsage = $operationalEntries
            ->where('transaction_type', 'operasional_langsung')
            ->groupBy('fund_source')
            ->map(fn (Collection $items) => (int) $items->sum('obligation_amount'));

        $settlementSavingUsage = $groupedSavingAllocations
            ->mapWithKeys(function (array $saving) use ($talanganEntries) {
                $settled = $talanganEntries
                    ->sum(function (ControlEntry $entry) use ($saving) {
                        return $entry->savingAllocationSettlements
                            ->filter(fn ($settlement) => $settlement->savingAllocation?->source_name === $saving['source'])
                            ->sum('amount');
                    });

                return [$saving['source'] => (int) $settled];
            });

        $tableTwoTwo = $tableTwoOne
            ->map(function (array $saving) use ($directSavingUsage, $settlementSavingUsage) {
                $directUsed = (int) ($directSavingUsage[$saving['source']] ?? 0);
                $settledUsed = (int) ($settlementSavingUsage[$saving['source']] ?? 0);
                $used = $directUsed + $settledUsed;

                return [
                    'source' => $saving['source'],
                    'allocation' => $saving['amount'],
                    'used' => $used,
                    'direct_used' => $directUsed,
                    'settled_used' => $settledUsed,
                    'balance' => $saving['amount'] - $used,
                    'usage_percent' => $saving['amount'] > 0 ? (int) round(($used / $saving['amount']) * 100) : 0,
                ];
            })
            ->values();

        $totalSaving = (int) $tableTwoOne->sum('amount');
        $overallUsage = (int) $operationalEntries->sum('obligation_amount');
        $savingUsage = (int) $tableTwoTwo->sum('used');
        $activeDebtTotal = (int) $talanganEntries->sum(fn (ControlEntry $entry) => $entry->remainingDebt());

        $tableTwo = [
            'total_saving' => $totalSaving,
            'overall_usage' => $overallUsage,
            'ending_balance' => $totalSaving - $overallUsage,
            'notes' => $this->buildSavingNotes($savingAllocations, $period['label']),
        ];

        $tableThree = [
            'total_saving' => $totalSaving,
            'saving_usage' => $savingUsage,
            'ending_balance' => $totalSaving - $savingUsage,
        ];

        $summaryCards = [
            [
                'label' => 'Operasional '.$period['label'],
                'amount' => $overallUsage,
                'caption' => $operationalEntries->count().' transaksi operasional tercatat',
                'accent' => 'sky',
            ],
            [
                'label' => 'Total Dana Saving',
                'amount' => $totalSaving,
                'caption' => $tableTwoOne->count().' sumber saving aktif pada periode ini',
                'accent' => 'emerald',
            ],
            [
                'label' => 'Hutang Talangan Aktif',
                'amount' => $activeDebtTotal,
                'caption' => 'Sisa hutang bendahara atau karyawan yang belum lunas',
                'accent' => 'amber',
            ],
            [
                'label' => 'Saldo Saving',
                'amount' => $tableThree['ending_balance'],
                'caption' => $tableThree['ending_balance'] >= 0 ? 'Masih ada sisa dana saving' : 'Pemakaian saving melewati alokasi',
                'accent' => $tableThree['ending_balance'] >= 0 ? 'teal' : 'rose',
            ],
        ];

        $savingEntries = collect()
            ->merge(
                $operationalEntries
                    ->where('transaction_type', 'operasional_langsung')
                    ->filter(fn (ControlEntry $entry) => $tableTwoOne->contains(fn (array $saving) => $saving['source'] === $entry->fund_source))
                    ->map(fn (ControlEntry $entry) => [
                        'sort_date' => optional($entry->entry_date)->format('Y-m-d') ?? '',
                        'date' => optional($entry->entry_date)->format('d M Y'),
                        'purpose' => $entry->purpose,
                        'source' => $entry->fund_source,
                        'amount' => (int) $entry->obligation_amount,
                        'status' => 'Pemakaian Langsung',
                    ])
            )
            ->merge(
                $tableTwoOne
                    ->map(function (array $saving) use ($talanganEntries) {
                        $amount = $talanganEntries
                            ->sum(function (ControlEntry $entry) use ($saving) {
                                return $entry->savingAllocationSettlements
                                    ->filter(fn ($settlement) => $settlement->savingAllocation?->source_name === $saving['source'])
                                    ->sum('amount');
                            });

                        return [
                            'sort_date' => now()->format('Y-m-d'),
                            'date' => 'Periode aktif',
                            'purpose' => 'Auto pelunasan hutang talangan',
                            'source' => $saving['source'],
                            'amount' => (int) $amount,
                            'status' => 'Pelunasan Otomatis',
                        ];
                    })
                    ->filter(fn (array $entry) => $entry['amount'] > 0)
            )
            ->sortByDesc('sort_date')
            ->values()
            ->map(function (array $entry) {
                unset($entry['sort_date']);

                return $entry;
            })
            ->take(8)
            ->values();

        $latestEntries = $entries
            ->take(6)
            ->map(fn (ControlEntry $entry) => [
                'date' => optional($entry->entry_date)->format('d M Y'),
                'purpose' => $entry->purpose,
                'source' => $entry->fund_source,
                'amount' => (int) ($entry->transaction_type === 'saving_masuk' ? $entry->amount_in : $entry->obligation_amount),
                'status' => $entry->status,
            ])
            ->values();

        $topSource = $tableOne->sortByDesc('total')->first();

        return view('dasborapp', [
            'title' => 'Dashboard Rekap',
            'periodLabel' => $period['label'].' '.$period['year'],
            'currentPeriod' => $period,
            'monthOptions' => $this->monthOptions(),
            'yearOptions' => $this->yearOptions(),
            'summaryCards' => $summaryCards,
            'tableOne' => $tableOne,
            'tableOneTotals' => $tableOneTotals,
            'tableTwo' => $tableTwo,
            'tableTwoOne' => $tableTwoOne,
            'tableTwoTwo' => $tableTwoTwo,
            'tableThree' => $tableThree,
            'savingEntries' => $savingEntries,
            'latestEntries' => $latestEntries,
            'canManageSaving' => auth()->user()?->hasAnyRole(['admin', 'bendahara']) ?? false,
            'stats' => [
                'transactionCount' => $entries->count(),
                'activeFundSources' => $tableOne->filter(fn (array $row) => $row['total'] > 0)->count(),
                'savingSourcesActive' => $tableTwoTwo->filter(fn (array $row) => $row['used'] > 0)->count(),
                'outstandingSources' => $tableOne->filter(fn (array $row) => $row['hutang'] > 0)->count(),
                'topSource' => $topSource['source'] ?? '-',
                'topSourceAmount' => (int) ($topSource['total'] ?? 0),
            ],
        ]);
    }

    private function buildTableOne(Collection $entries, Collection $savingSources): Collection
    {
        return collect(self::NON_SAVING_SOURCE_ORDER)
            ->merge($savingSources)
            ->merge($entries->pluck('fund_source'))
            ->unique()
            ->map(function (string $source) use ($entries) {
                $sourceEntries = $entries->where('fund_source', $source);

                $hutang = (int) $sourceEntries
                    ->where('transaction_type', 'operasional_talangan')
                    ->sum(fn (ControlEntry $entry) => $entry->remainingDebt());

                $partial = (int) $sourceEntries
                    ->where('transaction_type', 'operasional_talangan')
                    ->filter(fn (ControlEntry $entry) => $entry->remainingDebt() > 0 && $entry->settledAmount() > 0)
                    ->sum(fn (ControlEntry $entry) => $entry->settledAmount());

                $lunas = (int) $sourceEntries
                    ->filter(function (ControlEntry $entry) {
                        if ($entry->transaction_type === 'operasional_langsung') {
                            return true;
                        }

                        return $entry->transaction_type === 'operasional_talangan' && $entry->remainingDebt() === 0;
                    })
                    ->sum('obligation_amount');

                return [
                    'source' => $source,
                    'hutang' => $hutang,
                    'lunas' => $lunas,
                    'partial' => $partial,
                    'total' => (int) $sourceEntries->sum('obligation_amount'),
                ];
            })
            ->values();
    }

    private function buildSavingNotes(Collection $savingAllocations, string $periodLabel): Collection
    {
        return $savingAllocations
            ->values()
            ->map(function (SavingAllocation $allocation, int $index) use ($periodLabel) {
                return [
                    'label' => $index === 0 ? 'Saving Awal '.$periodLabel : 'Tambahan Saving '.$index,
                    'amount' => (int) $allocation->amount,
                ];
            });
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
