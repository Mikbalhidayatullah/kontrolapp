<?php

namespace App\Http\Controllers;

use App\Models\ControlEntry;
use App\Models\SavingAllocation;
use App\Models\SavingReduction;
use App\Services\SavingCarryOverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly SavingCarryOverService $carryOverService,
    ) {
    }

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

    public function index(Request $request): View|RedirectResponse
    {
        if (auth()->user()->role === 'verifikator') {
            return redirect()->route('perjadin');
        }

        $period = $this->selectedPeriod($request);

        $entries = ControlEntry::query()
            ->with(['debtSettlements', 'savingSettlements', 'savingAllocationSettlements.savingAllocation'])
            ->where('transaction_type', 'operasional_langsung')
            ->whereYear('entry_date', $period['year'])
            ->whereMonth('entry_date', $period['month'])
            ->latest('entry_date')
            ->latest()
            ->get();

        $operationalEntries = $entries;

        $savingAllocations = SavingAllocation::query()
            ->where('period_month', $period['month'])
            ->where('period_year', $period['year'])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('source_name')
            ->orderBy('created_at')
            ->get();

        $savingReductions = SavingReduction::query()
            ->where('period_month', $period['month'])
            ->where('period_year', $period['year'])
            ->get();

        $groupedSavingAllocations = $this->carryOverService
            ->effectiveAllocations($period['month'], $period['year'])
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
            ->groupBy('fund_source')
            ->map(fn (Collection $items) => (int) $items->sum('obligation_amount'));

        $tableTwoTwo = $this->carryOverService
            ->balanceRows($period['month'], $period['year'])
            ->values();
        $forwardTransfers = $this->carryOverService->forwardTransfersForPeriod($period['month'], $period['year']);

        $totalSaving = (int) $tableTwoOne->sum('amount');
        $overallUsage = (int) $operationalEntries->sum('obligation_amount');
        $savingUsage = (int) $tableTwoTwo->sum('used');
        $reductionTotal = (int) $savingReductions->sum('amount');
        $transferredForwardTotal = (int) $forwardTransfers->sum('amount');
        $remainingReimbursement = max($totalSaving - $reductionTotal - $transferredForwardTotal, 0);

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
                'caption' => $tableTwoOne->count().' sumber saving efektif pada periode ini',
                'accent' => 'emerald',
            ],
            [
                'label' => 'Pengurangan Saving',
                'amount' => $reductionTotal,
                'caption' => $savingReductions->count().' histori pengurangan saving pada periode ini',
                'accent' => 'amber',
            ],
            [
                'label' => 'Sisa Bayar Balik Saving',
                'amount' => $remainingReimbursement,
                'caption' => 'Nominal saving pribadi yang masih perlu dibayarkan kembali setelah perpindahan ke bulan berikutnya',
                'accent' => 'teal',
            ],
        ];

        $savingEntries = collect()
            ->merge(
                $operationalEntries
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
                'amount' => (int) $entry->obligation_amount,
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
                'reductionCount' => $savingReductions->count(),
                'topSource' => $topSource['source'] ?? '-',
                'topSourceAmount' => (int) ($topSource['total'] ?? 0),
            ],
        ]);
    }

    private function buildTableOne(Collection $entries, Collection $savingSources): Collection
    {
        return collect($entries->pluck('fund_source'))
            ->unique()
            ->filter(fn ($source) => filled($source))
            ->map(function (string $source) use ($entries, $savingSources) {
                $sourceEntries = $entries->where('fund_source', $source);
                $hutang = (int) $sourceEntries->where('status', 'HUTANG')->sum('obligation_amount');
                $partial = (int) $sourceEntries->where('status', 'BAYAR SEBAGIAN')->sum('obligation_amount');
                $lunas = (int) $sourceEntries->where('status', 'LUNAS')->sum('obligation_amount');

                return [
                    'source' => $source,
                    'hutang' => $hutang,
                    'lunas' => $lunas,
                    'partial' => $partial,
                    'total' => (int) $sourceEntries->sum('obligation_amount'),
                    'is_saving' => $savingSources->contains($source),
                ];
            })
            ->sortBy([
                ['is_saving', 'asc'],
                ['source', 'asc'],
            ])
            ->values()
            ->map(function (array $row) {
                unset($row['is_saving']);

                return $row;
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
