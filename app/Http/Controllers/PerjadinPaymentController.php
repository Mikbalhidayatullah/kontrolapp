<?php

namespace App\Http\Controllers;

use App\Models\PerjadinEntry;
use App\Models\PerjadinPaymentGroup;
use App\Services\PerjadinPaymentExcelExporter;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PerjadinPaymentController extends Controller
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

    public function index(Request $request): View
    {
        $period = $this->selectedPeriod($request);
        $selectedKeyword = trim($request->string('keyword')->toString());
        $entries = $this->paymentEntries($period, $selectedKeyword);
        $groups = $this->paymentGroups($entries);
        $pendingGroups = $groups->filter(fn (array $group): bool => ! $group['isPrinted'])->values();
        $printedGroups = $groups->filter(fn (array $group): bool => $group['isPrinted'])->values();
        $exportGroups = $this->paymentGroups($this->paymentEntries(null, '', true))
            ->filter(fn (array $group): bool => ! $group['isPrinted'])
            ->values();

        return view('perjadin-payments.index', [
            'title' => 'Halaman Bayar',
            'currentPeriod' => $period,
            'periodLabel' => $this->periodLabel($period),
            'monthOptions' => $this->monthOptions(),
            'yearOptions' => $this->yearOptions(),
            'selectedKeyword' => $selectedKeyword,
            'pendingGroups' => $pendingGroups,
            'printedGroups' => $printedGroups,
            'pendingMonthGroups' => $this->groupsByMonth($pendingGroups),
            'printedMonthGroups' => $this->groupsByMonth($printedGroups),
            'exportGroups' => $exportGroups,
            'summary' => [
                'groupCount' => $pendingGroups->count(),
                'entryCount' => (int) $pendingGroups->sum(fn (array $group): int => $group['entries']->count()),
                'grandTotal' => (int) $pendingGroups->sum('total'),
                'printedGroupCount' => $printedGroups->count(),
                'printedGrandTotal' => (int) $printedGroups->sum('total'),
                'incompletePurposeCount' => $pendingGroups->filter(fn (array $group): bool => blank($group['paymentGroup']->purpose))->count(),
            ],
        ]);
    }

    public function markPrinted(Request $request, PerjadinPaymentGroup $paymentGroup): RedirectResponse
    {
        $updated = PerjadinEntry::query()
            ->whereNotNull('paid_at')
            ->whereNull('payment_printed_at')
            ->where('assignment_number', $paymentGroup->assignment_number)
            ->whereDate('assignment_date', $paymentGroup->assignment_date)
            ->update([
                'payment_printed_at' => now(),
                'payment_printed_by' => $request->user()->id,
                'updated_at' => now(),
            ]);

        return redirect()->route('perjadin-payments.index', [
            'month' => $request->string('month')->toString(),
            'year' => $request->string('year')->toString(),
            'keyword' => trim($request->string('keyword')->toString()),
        ])->with('status', $updated > 0 ? 'Surat tugas berhasil ditandai sudah dicetak.' : 'Surat tugas ini sudah dicetak sebelumnya.');
    }

    public function exportExcel(Request $request, PerjadinPaymentExcelExporter $exporter): BinaryFileResponse|RedirectResponse
    {
        $data = $request->validate([
            'payment_group_ids' => ['required', 'array', 'min:1'],
            'payment_group_ids.*' => ['integer', Rule::exists('perjadin_payment_groups', 'id')],
        ], [
            'payment_group_ids.required' => 'Pilih minimal satu nomor surat tugas untuk dimasukkan ke Excel.',
            'payment_group_ids.min' => 'Pilih minimal satu nomor surat tugas untuk dimasukkan ke Excel.',
        ]);

        $paymentGroups = PerjadinPaymentGroup::query()
            ->whereIn('id', $data['payment_group_ids'])
            ->orderBy('assignment_date')
            ->orderBy('assignment_number')
            ->get();
        $entries = $this->paidEntriesForPaymentGroups($paymentGroups);
        $groups = $this->paymentGroups($entries);
        $missingPurposes = $groups->filter(fn (array $group): bool => blank($group['paymentGroup']->purpose));

        if ($groups->isEmpty()) {
            return redirect()->route('perjadin-payments.index')->withErrors([
                'payment_group_ids' => 'Data yang dipilih sudah dicetak atau tidak tersedia untuk export.',
            ]);
        }

        if ($missingPurposes->isNotEmpty()) {
            return redirect()->route('perjadin-payments.index')->withErrors([
                'purpose' => 'Lengkapi tujuan/kegiatan pada setiap surat tugas sebelum download Excel.',
            ]);
        }

        $path = $exporter->export($groups, [
            'month' => now()->month,
            'year' => now()->year,
            'label' => self::PERIOD_MONTHS[now()->month],
        ]);

        return response()
            ->download($path, 'daftar-penerimaan-perjadin-'.now()->format('Ymd-His').'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    private function paymentEntries(?array $period = null, string $keyword = '', bool $onlyPendingPrint = false): Collection
    {
        $query = PerjadinEntry::query()
            ->whereNotNull('paid_at')
            ->when($onlyPendingPrint, fn ($query) => $query->whereNull('payment_printed_at'))
            ->orderBy('start_date')
            ->orderBy('assignment_date')
            ->orderBy('assignment_number')
            ->orderBy('id');

        if (($period['year'] ?? null) !== null) {
            $query->whereYear('start_date', $period['year']);
        }

        if (($period['month'] ?? null) !== null) {
            $query->whereMonth('start_date', $period['month']);
        }

        if ($keyword !== '') {
            $query->where(function ($innerQuery) use ($keyword): void {
                $innerQuery
                    ->where('executor_name', 'like', '%'.$keyword.'%')
                    ->orWhere('assignment_number', 'like', '%'.$keyword.'%')
                    ->orWhere('destination_city', 'like', '%'.$keyword.'%')
                    ->orWhere('origin_regency', 'like', '%'.$keyword.'%')
                    ->orWhere('origin_district', 'like', '%'.$keyword.'%')
                    ->orWhere('destination_regency', 'like', '%'.$keyword.'%')
                    ->orWhere('destination_district', 'like', '%'.$keyword.'%')
                    ->orWhere('skpd_name', 'like', '%'.$keyword.'%')
                    ->orWhere('position_name', 'like', '%'.$keyword.'%')
                    ->orWhereExists(function ($purposeQuery) use ($keyword): void {
                        $purposeQuery
                            ->select(DB::raw(1))
                            ->from('perjadin_payment_groups')
                            ->whereColumn('perjadin_payment_groups.assignment_number', 'perjadin_entries.assignment_number')
                            ->whereColumn('perjadin_payment_groups.assignment_date', 'perjadin_entries.assignment_date')
                            ->where('perjadin_payment_groups.purpose', 'like', '%'.$keyword.'%');
                    });
            });
        }

        return $query->get();
    }

    private function paidEntriesForPaymentGroups(Collection $paymentGroups): Collection
    {
        if ($paymentGroups->isEmpty()) {
            return collect();
        }

        return PerjadinEntry::query()
            ->whereNotNull('paid_at')
            ->whereNull('payment_printed_at')
            ->where(function ($query) use ($paymentGroups): void {
                foreach ($paymentGroups as $paymentGroup) {
                    $query->orWhere(function ($assignmentQuery) use ($paymentGroup): void {
                        $assignmentQuery
                            ->where('assignment_number', $paymentGroup->assignment_number)
                            ->whereDate('assignment_date', $paymentGroup->assignment_date);
                    });
                }
            })
            ->orderBy('start_date')
            ->orderBy('assignment_date')
            ->orderBy('assignment_number')
            ->orderBy('id')
            ->get();
    }

    private function paymentGroups(Collection $entries): Collection
    {
        return $entries
            ->groupBy(fn (PerjadinEntry $entry): string => $this->assignmentKey($entry))
            ->map(function (Collection $groupEntries): array {
                /** @var PerjadinEntry $firstEntry */
                $firstEntry = $groupEntries->first();
                $paymentGroup = PerjadinPaymentGroup::query()->firstOrCreate([
                    'assignment_number' => $firstEntry->assignment_number,
                    'assignment_date' => $firstEntry->assignment_date->format('Y-m-d'),
                ]);

                return [
                    'paymentGroup' => $paymentGroup,
                    'entries' => $groupEntries->values(),
                    'destination' => $firstEntry->destination_city ?: $firstEntry->destination_regency ?: '-',
                    'periodLabel' => $this->travelPeriodLabel($groupEntries),
                    'total' => (int) $groupEntries->sum('grand_total'),
                    'monthKey' => optional($groupEntries->sortBy('start_date')->first()?->start_date)->format('Y-m') ?: 'tanpa-tanggal',
                    'monthLabel' => optional($groupEntries->sortBy('start_date')->first()?->start_date)->translatedFormat('F Y') ?: 'Tanpa Tanggal',
                    'isPrinted' => $groupEntries->every(fn (PerjadinEntry $entry): bool => filled($entry->payment_printed_at)),
                    'printedAt' => $groupEntries->max('payment_printed_at'),
                ];
            })
            ->values();
    }

    private function groupsByMonth(Collection $groups): Collection
    {
        return $groups
            ->groupBy('monthKey')
            ->map(fn (Collection $monthGroups): array => [
                'label' => $monthGroups->first()['monthLabel'],
                'groups' => $monthGroups->values(),
                'total' => (int) $monthGroups->sum('total'),
                'entryCount' => (int) $monthGroups->sum(fn (array $group): int => $group['entries']->count()),
            ])
            ->values();
    }

    private function assignmentKey(PerjadinEntry $entry): string
    {
        return $entry->assignment_number.'|'.$entry->assignment_date->format('Y-m-d');
    }

    private function travelPeriodLabel(Collection $entries): string
    {
        $startDate = $entries->sortBy('start_date')->first()?->start_date;
        $endDate = $entries->sortByDesc('end_date')->first()?->end_date;

        return $this->dateLabel($startDate).' s/d '.$this->dateLabel($endDate);
    }

    private function dateLabel(mixed $date): string
    {
        if (! $date) {
            return '-';
        }

        if (! $date instanceof CarbonInterface) {
            $date = Carbon::parse($date);
        }

        return $date->translatedFormat('d F Y');
    }

    private function selectedPeriod(Request $request): array
    {
        $monthValue = $request->string('month')->toString();
        $yearValue = $request->string('year')->toString();
        $month = $monthValue !== '' ? (int) $monthValue : null;
        $year = ctype_digit($yearValue) ? (int) $yearValue : null;

        if ($month !== null && ! array_key_exists($month, self::PERIOD_MONTHS)) {
            $month = null;
        }

        return [
            'month' => $month,
            'year' => $year,
            'label' => $month !== null ? self::PERIOD_MONTHS[$month] : 'Semua Bulan',
        ];
    }

    private function periodLabel(array $period): string
    {
        if (($period['month'] ?? null) !== null && ($period['year'] ?? null) !== null) {
            return $period['label'].' '.$period['year'];
        }

        if (($period['month'] ?? null) !== null) {
            return $period['label'];
        }

        if (($period['year'] ?? null) !== null) {
            return 'Tahun '.$period['year'];
        }

        return 'Semua Bulan';
    }

    private function monthOptions(): array
    {
        return collect(self::PERIOD_MONTHS)
            ->map(fn (string $label, int $month): array => [
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
