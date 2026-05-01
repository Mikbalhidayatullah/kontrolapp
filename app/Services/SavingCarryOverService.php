<?php

namespace App\Services;

use App\Models\ControlEntry;
use App\Models\SavingAllocation;
use Illuminate\Support\Collection;

class SavingCarryOverService
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

    private array $effectiveCache = [];
    private array $balanceCache = [];
    private array $carryOverCache = [];
    private array $forwardTransferCache = [];

    public function effectiveAllocations(int $month, int $year): Collection
    {
        $cacheKey = $this->cacheKey($month, $year);

        if (array_key_exists($cacheKey, $this->effectiveCache)) {
            return $this->effectiveCache[$cacheKey];
        }

        $manualAllocations = SavingAllocation::query()
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('source_name')
            ->orderBy('created_at')
            ->get();

        $manualBySource = $manualAllocations->groupBy('source_name');
        $carryOvers = $this->carryOversForPeriod($month, $year);

        $rows = collect(self::SOURCE_OPTIONS)
            ->filter(function (string $source) use ($manualBySource, $carryOvers) {
                return $manualBySource->has($source) || $carryOvers->has($source);
            })
            ->map(function (string $source) use ($manualBySource, $carryOvers) {
                $manualItems = $manualBySource->get($source, collect());
                $carryOver = $carryOvers->get($source, [
                    'amount' => 0,
                    'label' => null,
                ]);

                $carryAmount = (int) ($carryOver['amount'] ?? 0);
                $manualAmount = (int) $manualItems->sum('amount');

                return [
                    'source' => $source,
                    'amount' => $manualAmount + $carryAmount,
                    'manual_amount' => $manualAmount,
                    'carry_over_amount' => $carryAmount,
                    'carry_over_label' => $carryOver['label'] ?? null,
                    'entries_count' => $manualItems->count(),
                    'effective_entries_count' => $manualItems->count() + ($carryAmount !== 0 ? 1 : 0),
                    'sort_order' => $this->sortOrderForSource($source),
                ];
            })
            ->values();

        return $this->effectiveCache[$cacheKey] = $rows;
    }

    public function balanceRows(int $month, int $year): Collection
    {
        $cacheKey = $this->cacheKey($month, $year);

        if (array_key_exists($cacheKey, $this->balanceCache)) {
            return $this->balanceCache[$cacheKey];
        }

        $usageBySource = ControlEntry::query()
            ->where('transaction_type', 'operasional_langsung')
            ->whereYear('entry_date', $year)
            ->whereMonth('entry_date', $month)
            ->selectRaw('fund_source, SUM(obligation_amount) as total')
            ->groupBy('fund_source')
            ->pluck('total', 'fund_source');

        $rows = $this->effectiveAllocations($month, $year)
            ->map(function (array $row) use ($usageBySource) {
                $used = (int) ($usageBySource[$row['source']] ?? 0);

                return array_merge($row, [
                    'allocation' => (int) $row['amount'],
                    'used' => $used,
                    'direct_used' => $used,
                    'settled_used' => 0,
                    'balance' => $row['amount'] - $used,
                    'usage_percent' => $row['amount'] > 0 ? (int) round(($used / $row['amount']) * 100) : 0,
                ]);
            })
            ->values();

        return $this->balanceCache[$cacheKey] = $rows;
    }

    public function carryOversForPeriod(int $month, int $year): Collection
    {
        $cacheKey = $this->cacheKey($month, $year);

        if (array_key_exists($cacheKey, $this->carryOverCache)) {
            return $this->carryOverCache[$cacheKey];
        }

        $previousPeriod = $this->previousPeriod($month, $year);

        if ($previousPeriod === null) {
            return $this->carryOverCache[$cacheKey] = collect();
        }

        $carryOvers = $this->balanceRows($previousPeriod['month'], $previousPeriod['year'])
            ->filter(fn (array $row) => $row['balance'] !== 0)
            ->mapWithKeys(function (array $row) use ($previousPeriod) {
                return [
                    $row['source'] => [
                        'amount' => (int) $row['balance'],
                        'label' => 'Dana Saving Bulan '.$previousPeriod['label'],
                    ],
                ];
            });

        return $this->carryOverCache[$cacheKey] = $carryOvers;
    }

    public function forwardTransfersForPeriod(int $month, int $year): Collection
    {
        $cacheKey = $this->cacheKey($month, $year);

        if (array_key_exists($cacheKey, $this->forwardTransferCache)) {
            return $this->forwardTransferCache[$cacheKey];
        }

        $nextPeriod = $this->nextPeriod($month, $year);

        if ($nextPeriod === null) {
            return $this->forwardTransferCache[$cacheKey] = collect();
        }

        $transfers = $this->balanceRows($month, $year)
            ->filter(fn (array $row) => $row['balance'] > 0)
            ->mapWithKeys(function (array $row) use ($nextPeriod) {
                return [
                    $row['source'] => [
                        'amount' => (int) $row['balance'],
                        'label' => 'Dipindahkan ke Dana Saving Bulan '.$nextPeriod['label'],
                    ],
                ];
            });

        return $this->forwardTransferCache[$cacheKey] = $transfers;
    }

    private function previousPeriod(int $month, int $year): ?array
    {
        if ($year <= 2020 && $month === 1) {
            return null;
        }

        $previousMonth = $month - 1;
        $previousYear = $year;

        if ($previousMonth < 1) {
            $previousMonth = 12;
            $previousYear--;
        }

        return [
            'month' => $previousMonth,
            'year' => $previousYear,
            'label' => self::PERIOD_MONTHS[$previousMonth] ?? 'Periode',
        ];
    }

    private function nextPeriod(int $month, int $year): ?array
    {
        if ($year >= 2100 && $month === 12) {
            return null;
        }

        $nextMonth = $month + 1;
        $nextYear = $year;

        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }

        return [
            'month' => $nextMonth,
            'year' => $nextYear,
            'label' => self::PERIOD_MONTHS[$nextMonth] ?? 'Periode',
        ];
    }

    private function sortOrderForSource(string $source): int
    {
        $index = array_search($source, self::SOURCE_OPTIONS, true);

        return $index === false ? count(self::SOURCE_OPTIONS) + 1 : $index + 1;
    }

    private function cacheKey(int $month, int $year): string
    {
        return $year.'-'.$month;
    }
}
