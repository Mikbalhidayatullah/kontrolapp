<?php

namespace App\Http\Controllers;

use App\Models\LrfkEntry;
use App\Services\LrfkExcelExporter;
use App\Services\LrfkPerjadinService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LrfkController extends Controller
{
    private const VERSION_OPTIONS = [
        LrfkEntry::DATASET_LAMA => 'LRFK Lama',
        LrfkEntry::DATASET_PERUBAHAN => 'Data Rapat',
        LrfkEntry::DATASET_DATA_OLAHAN => 'LRFK Perubahan',
    ];

    private const LEVEL_OPTIONS = [
        'dinas' => 'Dinas',
        'belanja_daerah' => 'Belanja Daerah',
        'program' => 'Program',
        'kegiatan' => 'Kegiatan',
        'sub_kegiatan' => 'Sub Kegiatan',
        'rekening' => 'Rekening',
    ];

    public function __construct(private readonly LrfkPerjadinService $lrfkPerjadinService) {}

    public function index(Request $request): View
    {
        $selectedVersion = $this->selectedVersion($request);
        [$selectedKeyword, $selectedLevel] = $this->selectedFilters($request);
        $entries = $this->filteredEntries($selectedVersion, $selectedKeyword, $selectedLevel);
        $versionEntries = ($selectedKeyword === '' && $selectedLevel === '')
            ? $entries
            : $this->filteredEntries($selectedVersion, '', '');
        $metrics = $this->lrfkPerjadinService->metrics($versionEntries);
        $summaryEntries = $selectedVersion === LrfkEntry::DATASET_LAMA
            ? $entries
            : $versionEntries;

        return view('lrfk.index', [
            'title' => self::VERSION_OPTIONS[$selectedVersion],
            'entries' => $entries,
            'levelOptions' => self::LEVEL_OPTIONS,
            'selectedVersion' => $selectedVersion,
            'selectedVersionLabel' => self::VERSION_OPTIONS[$selectedVersion],
            'selectedKeyword' => $selectedKeyword,
            'selectedLevel' => $selectedLevel,
            'metrics' => $metrics,
            'linkedUsageByEntry' => $this->lrfkPerjadinService->linkedUsageByEntry(),
            'summary' => [
                'count' => $this->physicalRowCount($entries, $selectedVersion),
                'pagu' => $this->hierarchicalPaguTotal($summaryEntries),
                'contract' => $this->hierarchicalMetricTotal($summaryEntries, $metrics, 'contract'),
                'realization' => $this->hierarchicalMetricTotal($summaryEntries, $metrics, 'realization'),
            ],
        ]);
    }

    private function selectedVersion(Request $request): string
    {
        $version = $request->string('version')->toString();

        return array_key_exists($version, self::VERSION_OPTIONS) ? $version : LrfkEntry::DATASET_LAMA;
    }

    public function exportExcel(Request $request, LrfkExcelExporter $exporter): BinaryFileResponse
    {
        $selectedVersion = $this->selectedVersion($request);
        $entries = $this->filteredEntries($selectedVersion, '', '');
        $path = $exporter->export(
            $entries,
            $this->lrfkPerjadinService->metrics($entries),
            $this->lrfkPerjadinService->linkedUsageByEntry(),
            self::LEVEL_OPTIONS,
            [
                'keyword' => '',
                'level' => '',
                'version_label' => self::VERSION_OPTIONS[$selectedVersion],
            ],
            $selectedVersion,
        );

        return response()
            ->download($path, 'LRFK-'.ucfirst($selectedVersion).'-'.now()->format('Ymd-His').'.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    public function create(Request $request): View
    {
        $selectedVersion = $this->selectedVersion($request);

        return $this->formView('Tambah '.self::VERSION_OPTIONS[$selectedVersion], null, $selectedVersion);
    }

    public function store(Request $request): RedirectResponse
    {
        $selectedVersion = $this->selectedVersion($request);
        $data = [
            ...$this->validatedData($request, $selectedVersion),
            'dataset_version' => $selectedVersion,
        ];

        DB::transaction(function () use ($data, $request): void {
            $sortOrder = $this->nextSortOrderFor($data);

            LrfkEntry::query()->create([
                ...$data,
                'sort_order' => $sortOrder,
                'created_by' => $request->user()->id,
            ]);
        });

        return redirect()
            ->route('lrfk.index', ['version' => $selectedVersion])
            ->with('status', 'Data '.self::VERSION_OPTIONS[$selectedVersion].' berhasil ditambahkan.');
    }

    public function edit(LrfkEntry $lrfkEntry): View
    {
        return $this->formView(
            'Edit '.self::VERSION_OPTIONS[$lrfkEntry->dataset_version],
            $lrfkEntry,
            $lrfkEntry->dataset_version
        );
    }

    public function update(Request $request, LrfkEntry $lrfkEntry): RedirectResponse
    {
        $selectedVersion = $lrfkEntry->dataset_version;
        $data = [
            ...$this->validatedData($request, $selectedVersion),
            'dataset_version' => $selectedVersion,
        ];

        if ($lrfkEntry->children()->exists() && $data['level'] !== 'sub_kegiatan') {
            return back()
                ->withErrors(['level' => 'Sub kegiatan ini masih memiliki rekening. Pindahkan rekeningnya sebelum mengubah jenis baris.'])
                ->withInput();
        }

        DB::transaction(function () use ($data, $request, $lrfkEntry): void {
            $shouldMove = $data['level'] === 'rekening'
                && ((int) $lrfkEntry->parent_id !== (int) $data['parent_id'] || $lrfkEntry->level !== 'rekening');

            if ($shouldMove) {
                $data['sort_order'] = $this->nextSortOrderFor($data, $lrfkEntry->id);
            }

            $lrfkEntry->update([
                ...$data,
                'updated_by' => $request->user()->id,
            ]);
        });

        return redirect()
            ->route('lrfk.index', ['version' => $selectedVersion])
            ->with('status', 'Data '.self::VERSION_OPTIONS[$selectedVersion].' berhasil diperbarui.');
    }

    public function destroy(LrfkEntry $lrfkEntry): RedirectResponse
    {
        if ($lrfkEntry->children()->exists()) {
            return back()->withErrors([
                'lrfk' => 'Sub kegiatan ini masih memiliki rekening. Pindahkan atau hapus rekeningnya terlebih dahulu.',
            ]);
        }

        $lrfkEntry->delete();

        return redirect()
            ->route('lrfk.index', ['version' => $lrfkEntry->dataset_version])
            ->with('status', 'Data '.self::VERSION_OPTIONS[$lrfkEntry->dataset_version].' berhasil dihapus.');
    }

    private function formView(string $title, ?LrfkEntry $entry, string $selectedVersion): View
    {
        return view('lrfk.form', [
            'title' => $title,
            'entry' => $entry,
            'levelOptions' => self::LEVEL_OPTIONS,
            'selectedVersion' => $selectedVersion,
            'selectedVersionLabel' => self::VERSION_OPTIONS[$selectedVersion],
            'subKegiatanOptions' => LrfkEntry::query()
                ->where('dataset_version', $selectedVersion)
                ->where('level', 'sub_kegiatan')
                ->when($entry !== null, fn ($query) => $query->whereKeyNot($entry->id))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'kode_rekening', 'program_kegiatan']),
        ]);
    }

    private function selectedFilters(Request $request): array
    {
        $selectedKeyword = trim($request->string('keyword')->toString());
        $selectedLevel = $request->string('level')->toString();

        if ($selectedLevel !== '' && ! array_key_exists($selectedLevel, self::LEVEL_OPTIONS)) {
            $selectedLevel = '';
        }

        return [$selectedKeyword, $selectedLevel];
    }

    private function filteredEntries(string $selectedVersion, string $selectedKeyword, string $selectedLevel): Collection
    {
        return LrfkEntry::query()
            ->with('details')
            ->where('dataset_version', $selectedVersion)
            ->when($selectedLevel !== '', fn ($query) => $query->where('level', $selectedLevel))
            ->when($selectedKeyword !== '', function ($query) use ($selectedKeyword): void {
                $query->where(function ($innerQuery) use ($selectedKeyword): void {
                    $innerQuery
                        ->where('kode', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('kode_rekening', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('program_kegiatan', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('contract_number_date', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('implementer', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('output', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('location', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('notes', 'like', '%'.$selectedKeyword.'%');
                });
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function physicalRowCount(Collection $entries, string $selectedVersion): int
    {
        if ($selectedVersion === LrfkEntry::DATASET_LAMA) {
            return $entries->count();
        }

        return $entries->count() + $entries->sum(fn (LrfkEntry $entry): int => $entry->details->count());
    }

    private function hierarchicalPaguTotal($entries): int
    {
        foreach (array_keys(self::LEVEL_OPTIONS) as $level) {
            $levelEntries = $entries->where('level', $level);

            if ($levelEntries->isNotEmpty()) {
                return (int) $levelEntries->sum('pagu_anggaran');
            }
        }

        return 0;
    }

    private function hierarchicalMetricTotal($entries, array $metrics, string $metric): int
    {
        foreach (array_keys(self::LEVEL_OPTIONS) as $level) {
            $levelEntries = $entries->where('level', $level);

            if ($levelEntries->isNotEmpty()) {
                return (int) $levelEntries->sum(
                    fn (LrfkEntry $entry): int => (int) ($metrics[$entry->id][$metric] ?? $entry->{$metric === 'contract' ? 'contract_value' : 'financial_realization'})
                );
            }
        }

        return 0;
    }

    private function validatedData(Request $request, string $selectedVersion): array
    {
        $validated = $request->validate([
            'level' => ['required', 'string', Rule::in(array_keys(self::LEVEL_OPTIONS))],
            'parent_id' => [
                'nullable',
                'required_if:level,rekening',
                'integer',
                Rule::exists('lrfk_entries', 'id')->where(
                    fn ($query) => $query
                        ->where('level', 'sub_kegiatan')
                        ->where('dataset_version', $selectedVersion)
                ),
            ],
            'kode' => ['nullable', 'string', 'max:255'],
            'kode_rekening' => ['nullable', 'string', 'max:255'],
            'program_kegiatan' => ['required', 'string'],
            'pagu_anggaran' => ['nullable', 'string'],
            'contract_value' => ['nullable', 'string'],
            'contract_number_date' => ['nullable', 'string', 'max:255'],
            'implementer' => ['nullable', 'string', 'max:255'],
            'output' => ['nullable', 'string'],
            'volume' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:255'],
            'financial_realization' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ], [
            'parent_id.required_if' => 'Pilih sub kegiatan induk untuk rekening.',
            'parent_id.exists' => 'Sub kegiatan induk yang dipilih tidak ditemukan.',
        ]);

        $pagu = $this->moneyToInt($validated['pagu_anggaran'] ?? null);
        $realization = $this->moneyToInt($validated['financial_realization'] ?? null);
        $percent = $pagu > 0 ? round(($realization / $pagu) * 100, 2) : 0;

        return [
            'level' => $validated['level'],
            'parent_id' => $validated['level'] === 'rekening' ? (int) $validated['parent_id'] : null,
            'kode' => ($validated['kode'] ?? null) ?: null,
            'kode_rekening' => ($validated['kode_rekening'] ?? null) ?: null,
            'program_kegiatan' => $validated['program_kegiatan'],
            'pagu_anggaran' => $pagu,
            'contract_value' => $this->moneyToInt($validated['contract_value'] ?? null),
            'contract_number_date' => ($validated['contract_number_date'] ?? null) ?: null,
            'implementer' => ($validated['implementer'] ?? null) ?: null,
            'output' => ($validated['output'] ?? null) ?: null,
            'volume' => ($validated['volume'] ?? null) ?: null,
            'unit' => ($validated['unit'] ?? null) ?: null,
            'financial_realization' => $realization,
            'financial_percent' => $percent,
            'physical_percent' => $percent,
            'location' => ($validated['location'] ?? null) ?: null,
            'notes' => ($validated['notes'] ?? null) ?: null,
        ];
    }

    private function moneyToInt(null|string|int $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        return (int) preg_replace('/\D/', '', (string) $value);
    }

    private function nextSortOrderFor(array $data, ?int $ignoreId = null): int
    {
        if (($data['level'] ?? null) !== 'rekening' || empty($data['parent_id'])) {
            return ((int) LrfkEntry::query()
                ->where('dataset_version', $data['dataset_version'])
                ->max('sort_order')) + 1;
        }

        $parent = LrfkEntry::query()->find((int) $data['parent_id']);
        $lastChildSortOrder = LrfkEntry::query()
            ->where('dataset_version', $data['dataset_version'])
            ->where('parent_id', $parent?->id)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->max('sort_order');

        $insertAfter = $lastChildSortOrder !== null ? (int) $lastChildSortOrder : (int) $parent->sort_order;
        $sortOrder = $insertAfter + 1;

        LrfkEntry::query()
            ->where('dataset_version', $data['dataset_version'])
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('sort_order', '>=', $sortOrder)
            ->increment('sort_order');

        return $sortOrder;
    }
}
