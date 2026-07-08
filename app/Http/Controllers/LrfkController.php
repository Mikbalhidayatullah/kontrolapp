<?php

namespace App\Http\Controllers;

use App\Models\LrfkEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LrfkController extends Controller
{
    private const LEVEL_OPTIONS = [
        'dinas' => 'Dinas',
        'belanja_daerah' => 'Belanja Daerah',
        'program' => 'Program',
        'kegiatan' => 'Kegiatan',
        'sub_kegiatan' => 'Sub Kegiatan',
        'rekening' => 'Rekening',
    ];

    public function index(Request $request): View
    {
        $selectedKeyword = trim($request->string('keyword')->toString());
        $selectedLevel = $request->string('level')->toString();

        if ($selectedLevel !== '' && ! array_key_exists($selectedLevel, self::LEVEL_OPTIONS)) {
            $selectedLevel = '';
        }

        $entries = LrfkEntry::query()
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

        return view('lrfk.index', [
            'title' => 'LRFK',
            'entries' => $entries,
            'levelOptions' => self::LEVEL_OPTIONS,
            'selectedKeyword' => $selectedKeyword,
            'selectedLevel' => $selectedLevel,
            'summary' => [
                'count' => $entries->count(),
                'pagu' => $this->hierarchicalPaguTotal($entries),
                'contract' => (int) $entries->sum('contract_value'),
                'realization' => (int) $entries->sum('financial_realization'),
            ],
        ]);
    }

    public function create(): View
    {
        return $this->formView('Tambah LRFK');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $nextSortOrder = ((int) LrfkEntry::query()->max('sort_order')) + 1;

        LrfkEntry::query()->create([
            ...$data,
            'sort_order' => $nextSortOrder,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('lrfk.index')->with('status', 'Data LRFK berhasil ditambahkan.');
    }

    public function edit(LrfkEntry $lrfkEntry): View
    {
        return $this->formView('Edit LRFK', $lrfkEntry);
    }

    public function update(Request $request, LrfkEntry $lrfkEntry): RedirectResponse
    {
        $data = $this->validatedData($request);

        $lrfkEntry->update([
            ...$data,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('lrfk.index')->with('status', 'Data LRFK berhasil diperbarui.');
    }

    public function destroy(LrfkEntry $lrfkEntry): RedirectResponse
    {
        $lrfkEntry->delete();

        return redirect()->route('lrfk.index')->with('status', 'Data LRFK berhasil dihapus.');
    }

    private function formView(string $title, ?LrfkEntry $entry = null): View
    {
        return view('lrfk.form', [
            'title' => $title,
            'entry' => $entry,
            'levelOptions' => self::LEVEL_OPTIONS,
        ]);
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

    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'level' => ['required', 'string', Rule::in(array_keys(self::LEVEL_OPTIONS))],
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
        ]);

        $pagu = $this->moneyToInt($validated['pagu_anggaran'] ?? null);
        $realization = $this->moneyToInt($validated['financial_realization'] ?? null);
        $percent = $pagu > 0 ? round(($realization / $pagu) * 100, 2) : 0;

        return [
            'level' => $validated['level'],
            'kode' => $validated['kode'] ?: null,
            'kode_rekening' => $validated['kode_rekening'] ?: null,
            'program_kegiatan' => $validated['program_kegiatan'],
            'pagu_anggaran' => $pagu,
            'contract_value' => $this->moneyToInt($validated['contract_value'] ?? null),
            'contract_number_date' => $validated['contract_number_date'] ?: null,
            'implementer' => $validated['implementer'] ?: null,
            'output' => $validated['output'] ?: null,
            'volume' => $validated['volume'] ?: null,
            'unit' => $validated['unit'] ?: null,
            'financial_realization' => $realization,
            'financial_percent' => $percent,
            'physical_percent' => $percent,
            'location' => $validated['location'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ];
    }

    private function moneyToInt(null|string|int $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        return (int) preg_replace('/\D/', '', (string) $value);
    }
}
