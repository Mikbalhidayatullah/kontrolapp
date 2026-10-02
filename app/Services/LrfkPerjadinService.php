<?php

namespace App\Services;

use App\Models\LrfkEntry;
use App\Models\PerjadinEntry;
use App\Models\PerjadinPaymentGroup;
use Illuminate\Support\Collection;

class LrfkPerjadinService
{
    /**
     * Build the selectable LRFK path from the authoritative display order.
     */
    public function hierarchy(): array
    {
        $entries = LrfkEntry::query()
            ->whereIn('level', ['program', 'kegiatan', 'sub_kegiatan', 'rekening'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $metrics = $this->metrics($entries);
        $programs = [];
        $currentProgramId = null;
        $currentKegiatanId = null;
        $currentSubKegiatanId = null;

        foreach ($entries as $entry) {
            if ($entry->level === 'program') {
                $currentProgramId = $entry->id;
                $currentKegiatanId = null;
                $currentSubKegiatanId = null;
                $programs[$entry->id] = [
                    ...$this->optionData($entry),
                    'kegiatan' => [],
                ];

                continue;
            }

            if ($entry->level === 'kegiatan') {
                if ($currentProgramId === null || ! isset($programs[$currentProgramId])) {
                    continue;
                }

                $currentKegiatanId = $entry->id;
                $currentSubKegiatanId = null;
                $programs[$currentProgramId]['kegiatan'][$entry->id] = [
                    ...$this->optionData($entry),
                    'sub_kegiatan' => [],
                ];

                continue;
            }

            if ($entry->level === 'sub_kegiatan') {
                if ($currentProgramId === null || $currentKegiatanId === null) {
                    continue;
                }

                $currentSubKegiatanId = $entry->id;
                $programs[$currentProgramId]['kegiatan'][$currentKegiatanId]['sub_kegiatan'][$entry->id] = [
                    ...$this->optionData($entry),
                    'rekening' => [],
                ];

                continue;
            }

            if (
                $entry->level !== 'rekening'
                || blank($entry->kode_rekening)
                || $currentProgramId === null
                || $currentKegiatanId === null
                || $currentSubKegiatanId === null
            ) {
                continue;
            }

            $entryMetrics = $metrics[$entry->id] ?? $this->baseMetrics($entry);
            $programs[$currentProgramId]['kegiatan'][$currentKegiatanId]['sub_kegiatan'][$currentSubKegiatanId]['rekening'][$entry->id] = [
                ...$this->optionData($entry),
                'pagu' => (int) $entry->pagu_anggaran,
                'realisasi' => (int) $entryMetrics['realization'],
            ];
        }

        return collect($programs)
            ->map(function (array $program): array {
                $program['kegiatan'] = collect($program['kegiatan'])
                    ->map(function (array $kegiatan): array {
                        $kegiatan['sub_kegiatan'] = collect($kegiatan['sub_kegiatan'])
                            ->map(function (array $subKegiatan): array {
                                $subKegiatan['rekening'] = array_values($subKegiatan['rekening']);

                                return $subKegiatan;
                            })
                            ->values()
                            ->all();

                        return $kegiatan;
                    })
                    ->values()
                    ->all();

                return $program;
            })
            ->values()
            ->all();
    }

    public function metrics(?Collection $entries = null): array
    {
        $entries ??= LrfkEntry::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $linkedAmounts = PerjadinEntry::query()
            ->whereNotNull('lrfk_entry_id')
            ->whereNotNull('paid_at')
            ->selectRaw('lrfk_entry_id, SUM(grand_total) as amount')
            ->groupBy('lrfk_entry_id')
            ->pluck('amount', 'lrfk_entry_id')
            ->map(fn ($amount): int => (int) $amount);

        $metrics = [];
        $parentBaseMetrics = [];
        $paths = [
            'dinas' => null,
            'belanja_daerah' => null,
            'program' => null,
            'kegiatan' => null,
            'sub_kegiatan' => null,
        ];

        foreach ($entries as $entry) {
            if (array_key_exists($entry->level, $paths)) {
                $paths[$entry->level] = $entry->id;
                $this->resetLowerPaths($paths, $entry->level);
                $metrics[$entry->id] = ['contract' => 0, 'realization' => 0, 'linked' => 0];
                $parentBaseMetrics[$entry->id] = $this->baseMetrics($entry);

                continue;
            }

            if ($entry->level !== 'rekening') {
                continue;
            }

            $linkedAmount = (int) ($linkedAmounts[$entry->id] ?? 0);
            $contract = (int) $entry->contract_value + $linkedAmount;
            $realization = (int) $entry->financial_realization + $linkedAmount;
            $metrics[$entry->id] = [
                'contract' => $contract,
                'realization' => $realization,
                'linked' => $linkedAmount,
            ];

            foreach (array_filter($paths) as $pathId) {
                $metrics[$pathId] ??= ['contract' => 0, 'realization' => 0, 'linked' => 0];
                $metrics[$pathId]['contract'] += $contract;
                $metrics[$pathId]['realization'] += $realization;
                $metrics[$pathId]['linked'] += $linkedAmount;
            }
        }

        foreach ($parentBaseMetrics as $entryId => $baseMetrics) {
            if (($metrics[$entryId]['contract'] ?? 0) === 0 && ($metrics[$entryId]['realization'] ?? 0) === 0) {
                $metrics[$entryId] = $baseMetrics;
            }
        }

        return $metrics;
    }

    public function linkedUsageByEntry(): array
    {
        $entries = PerjadinEntry::query()
            ->whereNotNull('lrfk_entry_id')
            ->whereNotNull('paid_at')
            ->orderBy('assignment_date')
            ->orderBy('assignment_number')
            ->orderBy('id')
            ->get([
                'id',
                'lrfk_entry_id',
                'assignment_number',
                'assignment_date',
                'skpd_name',
                'grand_total',
            ]);

        if ($entries->isEmpty()) {
            return [];
        }

        $purposes = PerjadinPaymentGroup::query()
            ->whereIn('assignment_number', $entries->pluck('assignment_number')->filter()->unique()->values())
            ->get(['assignment_number', 'assignment_date', 'purpose'])
            ->keyBy(fn (PerjadinPaymentGroup $group): string => $this->assignmentKey(
                $group->assignment_number,
                optional($group->assignment_date)->format('Y-m-d')
            ));

        return $entries
            ->groupBy('lrfk_entry_id')
            ->map(function (Collection $accountEntries) use ($purposes): array {
                return $accountEntries
                    ->groupBy(fn (PerjadinEntry $entry): string => $this->assignmentKey(
                        $entry->assignment_number,
                        optional($entry->assignment_date)->format('Y-m-d')
                    ))
                    ->map(function (Collection $assignmentEntries, string $key) use ($purposes): array {
                        $first = $assignmentEntries->first();

                        return [
                            'assignment_number_date' => trim($first->assignment_number.' / '.optional($first->assignment_date)->format('d/m/Y'), ' /'),
                            'implementer' => $assignmentEntries->pluck('skpd_name')->filter()->unique()->implode(', '),
                            'purpose' => trim((string) ($purposes[$key]->purpose ?? '')),
                            'amount' => (int) $assignmentEntries->sum('grand_total'),
                        ];
                    })
                    ->values()
                    ->all();
            })
            ->all();
    }

    private function optionData(LrfkEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'kode' => (string) $entry->kode_rekening,
            'nama' => (string) $entry->program_kegiatan,
        ];
    }

    private function baseMetrics(LrfkEntry $entry): array
    {
        return [
            'contract' => (int) $entry->contract_value,
            'realization' => (int) $entry->financial_realization,
            'linked' => 0,
        ];
    }

    private function resetLowerPaths(array &$paths, string $level): void
    {
        $levels = array_keys($paths);
        $index = array_search($level, $levels, true);

        foreach (array_slice($levels, $index + 1) as $lowerLevel) {
            $paths[$lowerLevel] = null;
        }
    }

    private function assignmentKey(?string $number, ?string $date): string
    {
        return trim((string) $number).'|'.trim((string) $date);
    }
}
