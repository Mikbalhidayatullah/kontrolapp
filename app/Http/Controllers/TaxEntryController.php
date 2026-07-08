<?php

namespace App\Http\Controllers;

use App\Models\TaxEntry;
use App\Models\TaxTuEntry;
use App\Services\TaxExcelExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaxEntryController extends Controller
{
    private const ACCOUNT_OPTIONS = [
        'PPN',
        'PPnBM',
        'PPh 21',
        'PPh 22',
        'PPh 23',
        'PPh 25',
        'PPh 26',
        'PPh 4 Ayat 2',
        'PPh Final',
        'PBB',
        'Bea Meterai',
        'PBJT',
        'Pajak Daerah',
        'Pajak Lainnya',
    ];

    public function index(Request $request): View
    {
        $categories = $this->categoryOptions();
        $selectedCategory = $request->string('category')->toString();
        $selectedKeyword = trim($request->string('keyword')->toString());

        if ($selectedCategory !== '' && ! in_array($selectedCategory, $categories, true)) {
            $selectedCategory = '';
        }

        $entries = TaxEntry::query()
            ->with(['creator:id,name', 'updater:id,name'])
            ->when($selectedCategory !== '', fn ($query) => $query->where('category', $selectedCategory))
            ->when($selectedKeyword !== '', function ($query) use ($selectedKeyword): void {
                $query->where(function ($innerQuery) use ($selectedKeyword): void {
                    $innerQuery
                        ->where('proof_number', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('description', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('account_code', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('account_name', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('billing_id', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('ntpn', 'like', '%'.$selectedKeyword.'%');
                });
            })
            ->latest('entry_date')
            ->latest()
            ->get();

        $tuEntries = TaxTuEntry::query()
            ->with(['creator:id,name', 'updater:id,name'])
            ->when($selectedCategory !== '', fn ($query) => $query->where('category', $selectedCategory))
            ->when($selectedKeyword !== '', function ($query) use ($selectedKeyword): void {
                $query->where(function ($innerQuery) use ($selectedKeyword): void {
                    $innerQuery
                        ->where('kode_kegiatan', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('nama_belanja', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('sp2d_number', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('deposit_letter_number', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('ppn_billing_id', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('ppn_ntpn', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('pph21_billing_id', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('pph21_ntpn', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('pph22_billing_id', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('pph22_ntpn', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('pph23_billing_id', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('pph23_ntpn', 'like', '%'.$selectedKeyword.'%')
                        ->orWhere('notes', 'like', '%'.$selectedKeyword.'%');
                });
            })
            ->latest('sp2d_date')
            ->latest()
            ->get();

        return view('tax-entries.index', [
            'title' => 'Pajak',
            'entries' => $entries,
            'tuEntries' => $tuEntries,
            'printEntries' => $this->printEntries($entries, $tuEntries),
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'selectedKeyword' => $selectedKeyword,
            'summary' => [
                'count' => $entries->count() + $tuEntries->count(),
                'receiptTotal' => (int) $entries->sum('receipt_amount') + (int) $tuEntries->sum('requested_amount'),
                'expenseTotal' => (int) $entries->sum('expense_amount') + (int) $tuEntries->sum(fn (TaxTuEntry $entry): int => $entry->totalRealization()),
                'balanceTotal' => (int) $entries->sum('balance_amount') + (int) $tuEntries->sum(fn (TaxTuEntry $entry): int => $entry->depositBalance()),
            ],
        ]);
    }

    public function exportExcel(TaxExcelExporter $exporter)
    {
        $entries = TaxEntry::query()
            ->orderBy('category')
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $tuEntries = TaxTuEntry::query()
            ->orderBy('category')
            ->orderBy('sp2d_date')
            ->orderBy('id')
            ->get();

        $path = $exporter->export($entries, $tuEntries);

        return response()->download($path, 'data-pajak-'.now()->format('Ymd-His').'.xlsx')->deleteFileAfterSend(true);
    }

    public function create(): View
    {
        return $this->formView('Tambah Pajak');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->string('tax_format')->toString() === 'tu') {
            $data = $this->validatedTuBatchData($request);

            DB::transaction(function () use ($data, $request): void {
                foreach ($data['items'] as $item) {
                    TaxTuEntry::query()->create([
                        ...$item,
                        'category' => $data['category'],
                        'created_by' => $request->user()->id,
                    ]);
                }
            });

            return redirect()->route('pajak.index', ['category' => $data['category']])
                ->with('status', 'Data pajak TU berhasil ditambahkan.');
        }

        $data = $this->validatedBatchData($request);

        DB::transaction(function () use ($data, $request): void {
            foreach ($data['items'] as $item) {
                TaxEntry::query()->create([
                    ...$item,
                    'category' => $data['category'],
                    'created_by' => $request->user()->id,
                ]);
            }
        });

        return redirect()->route('pajak.index', ['category' => $data['category']])
            ->with('status', 'Data pajak berhasil ditambahkan.');
    }

    public function edit(TaxEntry $taxEntry): View
    {
        return $this->formView('Edit Pajak', $taxEntry);
    }

    public function editTu(TaxTuEntry $taxTuEntry): View
    {
        return $this->formView('Edit Pajak TU', null, $taxTuEntry);
    }

    public function update(Request $request, TaxEntry $taxEntry): RedirectResponse
    {
        $data = $this->validatedData($request);

        $taxEntry->update([
            ...$data,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('pajak.index', ['category' => $data['category']])
            ->with('status', 'Data pajak berhasil diperbarui.');
    }

    public function updateTu(Request $request, TaxTuEntry $taxTuEntry): RedirectResponse
    {
        $data = $this->validatedTuData($request);

        $taxTuEntry->update([
            ...$data,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('pajak.index', ['category' => $data['category']])
            ->with('status', 'Data pajak TU berhasil diperbarui.');
    }

    public function destroy(TaxEntry $taxEntry): RedirectResponse
    {
        $category = $taxEntry->category;
        $taxEntry->delete();

        return redirect()->route('pajak.index', ['category' => $category])
            ->with('status', 'Data pajak berhasil dihapus.');
    }

    public function destroyTu(TaxTuEntry $taxTuEntry): RedirectResponse
    {
        $category = $taxTuEntry->category;
        $taxTuEntry->delete();

        return redirect()->route('pajak.index', ['category' => $category])
            ->with('status', 'Data pajak TU berhasil dihapus.');
    }

    private function formView(string $title, ?TaxEntry $entry = null, ?TaxTuEntry $tuEntry = null): View
    {
        $categories = $this->categoryOptions();

        return view('tax-entries.form', [
            'title' => $title,
            'entry' => $entry,
            'tuEntry' => $tuEntry,
            'categories' => $categories,
            'accountOptions' => self::ACCOUNT_OPTIONS,
            'defaultEntryDate' => now()->format('Y-m-d'),
        ]);
    }

    private function validatedData(Request $request): array
    {
        $categories = $this->categoryOptions();
        $categoryMode = $categories === [] ? 'new' : $request->string('category_mode')->toString();
        $category = $categoryMode === 'new'
            ? trim($request->string('new_category')->toString())
            : $request->string('category')->toString();

        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'category_mode' => ['nullable', 'string', Rule::in(['existing', 'new'])],
            'category' => ['nullable', 'string', 'max:255'],
            'new_category' => ['nullable', 'string', 'max:255'],
            'proof_number' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'account_code' => ['nullable', 'string', 'max:255'],
            'account_name' => ['required', 'string', Rule::in(self::ACCOUNT_OPTIONS)],
            'billing_id' => ['nullable', 'string', 'max:255'],
            'ntpn' => ['nullable', 'string', 'max:255'],
            'receipt_amount' => ['nullable', 'string'],
            'expense_amount' => ['nullable', 'string'],
            'balance_amount' => ['nullable', 'string'],
        ]);

        if ($category === '') {
            $request->validate([
                $categoryMode === 'new' ? 'new_category' : 'category' => ['required'],
            ], [
                'new_category.required' => 'Kategori pajak wajib diisi.',
                'category.required' => 'Kategori pajak wajib dipilih.',
            ]);
        }

        return [
            'entry_date' => $validated['entry_date'],
            'category' => $category,
            'proof_number' => $validated['proof_number'],
            'description' => $validated['description'],
            'account_code' => $validated['account_code'] ?: null,
            'account_name' => $validated['account_name'],
            'billing_id' => $validated['billing_id'] ?: null,
            'ntpn' => $validated['ntpn'] ?: null,
            'receipt_amount' => $this->moneyToInt($validated['receipt_amount'] ?? null),
            'expense_amount' => $this->moneyToInt($validated['expense_amount'] ?? null),
            'balance_amount' => $this->moneyToInt($validated['balance_amount'] ?? null),
        ];
    }

    private function validatedBatchData(Request $request): array
    {
        $categories = $this->categoryOptions();
        $categoryMode = $categories === [] ? 'new' : $request->string('category_mode')->toString();
        $category = $categoryMode === 'new'
            ? trim($request->string('new_category')->toString())
            : $request->string('category')->toString();

        $validated = $request->validate([
            'category_mode' => ['nullable', 'string', Rule::in(['existing', 'new'])],
            'category' => ['nullable', 'string', 'max:255'],
            'new_category' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.entry_date' => ['required', 'date'],
            'items.*.proof_number' => ['required', 'string', 'max:255'],
            'items.*.description' => ['required', 'string'],
            'items.*.account_code' => ['nullable', 'string', 'max:255'],
            'items.*.account_name' => ['required', 'string', Rule::in(self::ACCOUNT_OPTIONS)],
            'items.*.billing_id' => ['nullable', 'string', 'max:255'],
            'items.*.ntpn' => ['nullable', 'string', 'max:255'],
            'items.*.receipt_amount' => ['nullable', 'string'],
            'items.*.expense_amount' => ['nullable', 'string'],
            'items.*.balance_amount' => ['nullable', 'string'],
        ], [
            'items.required' => 'Minimal satu data pajak wajib diisi.',
            'items.*.entry_date.required' => 'Tanggal wajib diisi.',
            'items.*.proof_number.required' => 'Nomor bukti wajib diisi.',
            'items.*.description.required' => 'Uraian wajib diisi.',
            'items.*.account_name.required' => 'Nama rekening wajib dipilih.',
        ]);

        if ($category === '') {
            $request->validate([
                $categoryMode === 'new' ? 'new_category' : 'category' => ['required'],
            ], [
                'new_category.required' => 'Kategori pajak wajib diisi.',
                'category.required' => 'Kategori pajak wajib dipilih.',
            ]);
        }

        return [
            'category' => $category,
            'items' => collect($validated['items'])
                ->map(fn (array $item): array => [
                    'entry_date' => $item['entry_date'],
                    'proof_number' => $item['proof_number'],
                    'description' => $item['description'],
                    'account_code' => ($item['account_code'] ?? '') !== '' ? $item['account_code'] : null,
                    'account_name' => $item['account_name'],
                    'billing_id' => ($item['billing_id'] ?? '') !== '' ? $item['billing_id'] : null,
                    'ntpn' => ($item['ntpn'] ?? '') !== '' ? $item['ntpn'] : null,
                    'receipt_amount' => $this->moneyToInt($item['receipt_amount'] ?? null),
                    'expense_amount' => $this->moneyToInt($item['expense_amount'] ?? null),
                    'balance_amount' => $this->moneyToInt($item['balance_amount'] ?? null),
                ])
                ->values()
                ->all(),
        ];
    }

    private function validatedTuData(Request $request): array
    {
        $categories = $this->categoryOptions();
        $categoryMode = $categories === [] ? 'new' : $request->string('category_mode')->toString();
        $category = $categoryMode === 'new'
            ? trim($request->string('new_category')->toString())
            : $request->string('category')->toString();

        $validated = $request->validate([
            'category_mode' => ['nullable', 'string', Rule::in(['existing', 'new'])],
            'category' => ['nullable', 'string', 'max:255'],
            'new_category' => ['nullable', 'string', 'max:255'],
            'kode_kegiatan' => ['nullable', 'string', 'max:255'],
            'nama_belanja' => ['required', 'string'],
            'sp2d_number' => ['nullable', 'string', 'max:255'],
            'sp2d_date' => ['nullable', 'date'],
            'pagu_amount' => ['nullable', 'string'],
            'requested_amount' => ['nullable', 'string'],
            'realization_1_amount' => ['nullable', 'string'],
            'realization_1_date' => ['nullable', 'date'],
            'realization_2_amount' => ['nullable', 'string'],
            'realization_2_date' => ['nullable', 'date'],
            'realization_3_amount' => ['nullable', 'string'],
            'realization_3_date' => ['nullable', 'date'],
            'realization_4_amount' => ['nullable', 'string'],
            'realization_4_date' => ['nullable', 'date'],
            'deposit_letter_number' => ['nullable', 'string', 'max:255'],
            'deposit_amount' => ['nullable', 'string'],
            'deposit_date' => ['nullable', 'date'],
            'ppn_amount' => ['nullable', 'string'],
            'ppn_billing_id' => ['nullable', 'string', 'max:255'],
            'ppn_ntpn' => ['nullable', 'string', 'max:255'],
            'pph21_amount' => ['nullable', 'string'],
            'pph21_billing_id' => ['nullable', 'string', 'max:255'],
            'pph21_ntpn' => ['nullable', 'string', 'max:255'],
            'pph22_amount' => ['nullable', 'string'],
            'pph22_billing_id' => ['nullable', 'string', 'max:255'],
            'pph22_ntpn' => ['nullable', 'string', 'max:255'],
            'pph23_amount' => ['nullable', 'string'],
            'pph23_billing_id' => ['nullable', 'string', 'max:255'],
            'pph23_ntpn' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ], [
            'nama_belanja.required' => 'Nama belanja wajib diisi.',
        ]);

        if ($category === '') {
            $request->validate([
                $categoryMode === 'new' ? 'new_category' : 'category' => ['required'],
            ], [
                'new_category.required' => 'Kategori pajak wajib diisi.',
                'category.required' => 'Kategori pajak wajib dipilih.',
            ]);
        }

        return [
            'category' => $category,
            ...$this->normalizeTuItem($validated),
        ];
    }

    private function validatedTuBatchData(Request $request): array
    {
        $categories = $this->categoryOptions();
        $categoryMode = $categories === [] ? 'new' : $request->string('category_mode')->toString();
        $category = $categoryMode === 'new'
            ? trim($request->string('new_category')->toString())
            : $request->string('category')->toString();

        $validated = $request->validate([
            'tax_format' => ['required', 'string', Rule::in(['gu_ls', 'tu'])],
            'category_mode' => ['nullable', 'string', Rule::in(['existing', 'new'])],
            'category' => ['nullable', 'string', 'max:255'],
            'new_category' => ['nullable', 'string', 'max:255'],
            'tu_items' => ['required', 'array', 'min:1'],
            'tu_items.*.kode_kegiatan' => ['nullable', 'string', 'max:255'],
            'tu_items.*.nama_belanja' => ['required', 'string'],
            'tu_items.*.sp2d_number' => ['nullable', 'string', 'max:255'],
            'tu_items.*.sp2d_date' => ['nullable', 'date'],
            'tu_items.*.pagu_amount' => ['nullable', 'string'],
            'tu_items.*.requested_amount' => ['nullable', 'string'],
            'tu_items.*.realization_1_amount' => ['nullable', 'string'],
            'tu_items.*.realization_1_date' => ['nullable', 'date'],
            'tu_items.*.realization_2_amount' => ['nullable', 'string'],
            'tu_items.*.realization_2_date' => ['nullable', 'date'],
            'tu_items.*.realization_3_amount' => ['nullable', 'string'],
            'tu_items.*.realization_3_date' => ['nullable', 'date'],
            'tu_items.*.realization_4_amount' => ['nullable', 'string'],
            'tu_items.*.realization_4_date' => ['nullable', 'date'],
            'tu_items.*.deposit_letter_number' => ['nullable', 'string', 'max:255'],
            'tu_items.*.deposit_amount' => ['nullable', 'string'],
            'tu_items.*.deposit_date' => ['nullable', 'date'],
            'tu_items.*.ppn_amount' => ['nullable', 'string'],
            'tu_items.*.ppn_billing_id' => ['nullable', 'string', 'max:255'],
            'tu_items.*.ppn_ntpn' => ['nullable', 'string', 'max:255'],
            'tu_items.*.pph21_amount' => ['nullable', 'string'],
            'tu_items.*.pph21_billing_id' => ['nullable', 'string', 'max:255'],
            'tu_items.*.pph21_ntpn' => ['nullable', 'string', 'max:255'],
            'tu_items.*.pph22_amount' => ['nullable', 'string'],
            'tu_items.*.pph22_billing_id' => ['nullable', 'string', 'max:255'],
            'tu_items.*.pph22_ntpn' => ['nullable', 'string', 'max:255'],
            'tu_items.*.pph23_amount' => ['nullable', 'string'],
            'tu_items.*.pph23_billing_id' => ['nullable', 'string', 'max:255'],
            'tu_items.*.pph23_ntpn' => ['nullable', 'string', 'max:255'],
            'tu_items.*.notes' => ['nullable', 'string'],
        ], [
            'tu_items.required' => 'Minimal satu data TU wajib diisi.',
            'tu_items.*.nama_belanja.required' => 'Nama belanja wajib diisi.',
        ]);

        if ($category === '') {
            $request->validate([
                $categoryMode === 'new' ? 'new_category' : 'category' => ['required'],
            ], [
                'new_category.required' => 'Kategori pajak wajib diisi.',
                'category.required' => 'Kategori pajak wajib dipilih.',
            ]);
        }

        return [
            'category' => $category,
            'items' => collect($validated['tu_items'])
                ->map(fn (array $item): array => $this->normalizeTuItem($item))
                ->values()
                ->all(),
        ];
    }

    private function normalizeTuItem(array $item): array
    {
        return [
            'kode_kegiatan' => ($item['kode_kegiatan'] ?? '') !== '' ? $item['kode_kegiatan'] : null,
            'nama_belanja' => $item['nama_belanja'],
            'sp2d_number' => ($item['sp2d_number'] ?? '') !== '' ? $item['sp2d_number'] : null,
            'sp2d_date' => ($item['sp2d_date'] ?? '') !== '' ? $item['sp2d_date'] : null,
            'pagu_amount' => $this->moneyToInt($item['pagu_amount'] ?? null),
            'requested_amount' => $this->moneyToInt($item['requested_amount'] ?? null),
            'realization_1_amount' => $this->moneyToInt($item['realization_1_amount'] ?? null),
            'realization_1_date' => ($item['realization_1_date'] ?? '') !== '' ? $item['realization_1_date'] : null,
            'realization_2_amount' => $this->moneyToInt($item['realization_2_amount'] ?? null),
            'realization_2_date' => ($item['realization_2_date'] ?? '') !== '' ? $item['realization_2_date'] : null,
            'realization_3_amount' => $this->moneyToInt($item['realization_3_amount'] ?? null),
            'realization_3_date' => ($item['realization_3_date'] ?? '') !== '' ? $item['realization_3_date'] : null,
            'realization_4_amount' => $this->moneyToInt($item['realization_4_amount'] ?? null),
            'realization_4_date' => ($item['realization_4_date'] ?? '') !== '' ? $item['realization_4_date'] : null,
            'deposit_letter_number' => ($item['deposit_letter_number'] ?? '') !== '' ? $item['deposit_letter_number'] : null,
            'deposit_amount' => $this->moneyToInt($item['deposit_amount'] ?? null),
            'deposit_date' => ($item['deposit_date'] ?? '') !== '' ? $item['deposit_date'] : null,
            'ppn_amount' => $this->moneyToInt($item['ppn_amount'] ?? null),
            'ppn_billing_id' => ($item['ppn_billing_id'] ?? '') !== '' ? $item['ppn_billing_id'] : null,
            'ppn_ntpn' => ($item['ppn_ntpn'] ?? '') !== '' ? $item['ppn_ntpn'] : null,
            'pph21_amount' => $this->moneyToInt($item['pph21_amount'] ?? null),
            'pph21_billing_id' => ($item['pph21_billing_id'] ?? '') !== '' ? $item['pph21_billing_id'] : null,
            'pph21_ntpn' => ($item['pph21_ntpn'] ?? '') !== '' ? $item['pph21_ntpn'] : null,
            'pph22_amount' => $this->moneyToInt($item['pph22_amount'] ?? null),
            'pph22_billing_id' => ($item['pph22_billing_id'] ?? '') !== '' ? $item['pph22_billing_id'] : null,
            'pph22_ntpn' => ($item['pph22_ntpn'] ?? '') !== '' ? $item['pph22_ntpn'] : null,
            'pph23_amount' => $this->moneyToInt($item['pph23_amount'] ?? null),
            'pph23_billing_id' => ($item['pph23_billing_id'] ?? '') !== '' ? $item['pph23_billing_id'] : null,
            'pph23_ntpn' => ($item['pph23_ntpn'] ?? '') !== '' ? $item['pph23_ntpn'] : null,
            'notes' => ($item['notes'] ?? '') !== '' ? $item['notes'] : null,
        ];
    }

    private function categoryOptions(): array
    {
        return TaxEntry::query()
            ->select('category')
            ->distinct()
            ->pluck('category')
            ->merge(TaxTuEntry::query()->select('category')->distinct()->pluck('category'))
            ->filter()
            ->unique()
            ->sort(fn (string $left, string $right): int => strnatcasecmp($left, $right))
            ->values()
            ->all();
    }

    private function printEntries(Collection $entries, Collection $tuEntries): array
    {
        $standardRows = collect($entries->map(fn (TaxEntry $entry): array => [
            'category' => $entry->category,
            'billing_id' => $entry->billing_id ?: '-',
            'amount' => (int) ($entry->expense_amount ?: $entry->receipt_amount),
        ])->all());

        $tuRows = $tuEntries->flatMap(function (TaxTuEntry $entry): array {
            return collect([
                ['billing_id' => $entry->ppn_billing_id, 'amount' => (int) $entry->ppn_amount],
                ['billing_id' => $entry->pph21_billing_id, 'amount' => (int) $entry->pph21_amount],
                ['billing_id' => $entry->pph22_billing_id, 'amount' => (int) $entry->pph22_amount],
                ['billing_id' => $entry->pph23_billing_id, 'amount' => (int) $entry->pph23_amount],
            ])
                ->filter(fn (array $row): bool => trim((string) $row['billing_id']) !== '' || $row['amount'] > 0)
                ->map(fn (array $row): array => [
                    'category' => $entry->category,
                    'billing_id' => $row['billing_id'] ?: '-',
                    'amount' => $row['amount'],
                ])
                ->values()
                ->all();
        });

        return $standardRows
            ->merge($tuRows)
            ->values()
            ->all();
    }

    private function moneyToInt(null|string|int $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        return (int) preg_replace('/\D/', '', (string) $value);
    }
}
