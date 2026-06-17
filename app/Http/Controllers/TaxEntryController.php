<?php

namespace App\Http\Controllers;

use App\Models\TaxEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('tax-entries.index', [
            'title' => 'Pajak',
            'entries' => $entries,
            'printEntries' => $entries->map(fn (TaxEntry $entry): array => [
                'category' => $entry->category,
                'billing_id' => $entry->billing_id ?: '-',
                'amount' => (int) ($entry->expense_amount ?: $entry->receipt_amount),
            ])->values()->all(),
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'selectedKeyword' => $selectedKeyword,
            'summary' => [
                'count' => $entries->count(),
                'receiptTotal' => (int) $entries->sum('receipt_amount'),
                'expenseTotal' => (int) $entries->sum('expense_amount'),
                'balanceTotal' => (int) $entries->sum('balance_amount'),
            ],
        ]);
    }

    public function create(): View
    {
        return $this->formView('Tambah Pajak');
    }

    public function store(Request $request): RedirectResponse
    {
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

    public function destroy(TaxEntry $taxEntry): RedirectResponse
    {
        $category = $taxEntry->category;
        $taxEntry->delete();

        return redirect()->route('pajak.index', ['category' => $category])
            ->with('status', 'Data pajak berhasil dihapus.');
    }

    private function formView(string $title, ?TaxEntry $entry = null): View
    {
        $categories = $this->categoryOptions();

        return view('tax-entries.form', [
            'title' => $title,
            'entry' => $entry,
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

    private function categoryOptions(): array
    {
        return TaxEntry::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->filter()
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
