<x-layout>
    <x-slot:title>{{ $title }}</x-slot:title>

    @php
        $isEdit = isset($entry) && $entry !== null;
        $formAction = $isEdit ? route('pajak.update', $entry) : route('pajak.store');
        $currentCategory = old('category', $isEdit ? $entry->category : '');
        $currentCategoryMode = old('category_mode', $categories === [] ? 'new' : 'existing');
        $moneyValue = fn ($value) => is_numeric($value) ? number_format((int) $value, 0, ',', '.') : $value;
        $itemRows = old('items', [[
            'entry_date' => $defaultEntryDate,
            'proof_number' => '',
            'description' => '',
            'account_code' => '',
            'account_name' => '',
            'billing_id' => '',
            'ntpn' => '',
            'receipt_amount' => '',
            'expense_amount' => '',
            'balance_amount' => '',
        ]]);
    @endphp

    <div class="space-y-6">
        <section class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-sky-600">{{ $isEdit ? 'Form Edit Pajak' : 'Form Input Pajak' }}</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ $isEdit ? 'Edit data pajak' : 'Tambah data pajak dalam satu kategori' }}</h2>
                    <p class="mt-2 text-sm text-slate-500">Kategori dipakai seperti periode, lalu isi satu atau beberapa data pajak di dalamnya.</p>
                </div>
                <a href="{{ route('pajak.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:text-sky-700">
                    Kembali ke Pajak
                </a>
            </div>

            <form action="{{ $formAction }}" method="POST" class="mt-8 space-y-8">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <section class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-100 text-sm font-semibold text-sky-700">01</div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Kategori / Periode</h3>
                            <p class="text-sm text-slate-500">Satu kategori dapat berisi beberapa data inputan pajak.</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Kategori</label>
                        @if ($categories === [])
                            <input type="hidden" name="category_mode" value="new" />
                            <input id="new_category" name="new_category" type="text" value="{{ old('new_category', $currentCategory) }}" placeholder="Contoh: Pajak Januari 2026" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                        @else
                            <div class="mt-2 grid gap-3 md:grid-cols-[220px_minmax(0,1fr)]">
                                <select id="category_mode" name="category_mode" data-category-mode class="rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                    <option value="existing" @selected($currentCategoryMode === 'existing')>Pilih kategori</option>
                                    <option value="new" @selected($currentCategoryMode === 'new')>Tambah kategori baru</option>
                                </select>
                                <select id="category" name="category" data-category-existing class="rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                    <option value="">Pilih kategori</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category }}" @selected($currentCategory === $category)>{{ $category }}</option>
                                    @endforeach
                                </select>
                                <input id="new_category" name="new_category" type="text" value="{{ old('new_category') }}" data-category-new placeholder="Nama kategori baru" class="hidden rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100 md:col-start-2" />
                            </div>
                        @endif
                        @error('category')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                        @error('new_category')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </section>

                @if (! $isEdit)
                    <section class="space-y-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-sm font-semibold text-emerald-700">02</div>
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">Data Pajak</h3>
                                    <p class="text-sm text-slate-500">Tambahkan beberapa data pajak di dalam kategori yang sama.</p>
                                </div>
                            </div>
                            <button type="button" data-add-tax-row class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                Tambah Data
                            </button>
                        </div>

                        @error('items')
                            <p class="rounded-2xl bg-rose-50 px-4 py-3 text-sm text-rose-600">{{ $message }}</p>
                        @enderror

                        <div class="space-y-5" data-tax-items-wrapper>
                            @foreach ($itemRows as $index => $item)
                                <article class="rounded-[24px] border border-slate-200 bg-slate-50 p-5" data-tax-row>
                                    <div class="mb-5 flex items-center justify-between gap-3 border-b border-slate-200 pb-4">
                                        <h4 class="text-base font-semibold text-slate-900" data-tax-row-title>Data Pajak {{ $loop->iteration }}</h4>
                                        <button type="button" data-remove-tax-row class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">
                                            Hapus
                                        </button>
                                    </div>

                                    <div class="grid gap-5 md:grid-cols-2">
                                        <div>
                                            <label for="items_{{ $index }}_entry_date" class="block text-sm font-medium text-slate-700">Tanggal</label>
                                            <input id="items_{{ $index }}_entry_date" name="items[{{ $index }}][entry_date]" type="date" value="{{ $item['entry_date'] ?? $defaultEntryDate }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                            @error("items.$index.entry_date")
                                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="items_{{ $index }}_proof_number" class="block text-sm font-medium text-slate-700">Nomor Bukti</label>
                                            <input id="items_{{ $index }}_proof_number" name="items[{{ $index }}][proof_number]" type="text" value="{{ $item['proof_number'] ?? '' }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                            @error("items.$index.proof_number")
                                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="md:col-span-2">
                                            <label for="items_{{ $index }}_description" class="block text-sm font-medium text-slate-700">Uraian</label>
                                            <textarea id="items_{{ $index }}_description" name="items[{{ $index }}][description]" rows="3" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">{{ $item['description'] ?? '' }}</textarea>
                                            @error("items.$index.description")
                                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="items_{{ $index }}_account_code" class="block text-sm font-medium text-slate-700">Kode Rekening</label>
                                            <input id="items_{{ $index }}_account_code" name="items[{{ $index }}][account_code]" type="text" value="{{ $item['account_code'] ?? '' }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                        </div>
                                        <div>
                                            <label for="items_{{ $index }}_account_name" class="block text-sm font-medium text-slate-700">Nama Rekening</label>
                                            <select id="items_{{ $index }}_account_name" name="items[{{ $index }}][account_name]" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                                <option value="">Pilih nama rekening</option>
                                                @foreach ($accountOptions as $option)
                                                    <option value="{{ $option }}" @selected(($item['account_name'] ?? '') === $option)>{{ $option }}</option>
                                                @endforeach
                                            </select>
                                            @error("items.$index.account_name")
                                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="items_{{ $index }}_billing_id" class="block text-sm font-medium text-slate-700">ID Billing</label>
                                            <input id="items_{{ $index }}_billing_id" name="items[{{ $index }}][billing_id]" type="text" value="{{ $item['billing_id'] ?? '' }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                        </div>
                                        <div>
                                            <label for="items_{{ $index }}_ntpn" class="block text-sm font-medium text-slate-700">NTPN</label>
                                            <input id="items_{{ $index }}_ntpn" name="items[{{ $index }}][ntpn]" type="text" value="{{ $item['ntpn'] ?? '' }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                        </div>
                                        <div>
                                            <label for="items_{{ $index }}_receipt_amount" class="block text-sm font-medium text-slate-700">Penerimaan</label>
                                            <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm transition focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100">
                                                <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                                <input id="items_{{ $index }}_receipt_amount" name="items[{{ $index }}][receipt_amount]" type="text" value="{{ $moneyValue($item['receipt_amount'] ?? '') }}" data-tax-money-input data-tax-money="receipt" class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                            </div>
                                        </div>
                                        <div>
                                            <label for="items_{{ $index }}_expense_amount" class="block text-sm font-medium text-slate-700">Pengeluaran</label>
                                            <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm transition focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100">
                                                <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                                <input id="items_{{ $index }}_expense_amount" name="items[{{ $index }}][expense_amount]" type="text" value="{{ $moneyValue($item['expense_amount'] ?? '') }}" data-tax-money-input data-tax-money="expense" class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                            </div>
                                        </div>
                                        <div>
                                            <label for="items_{{ $index }}_balance_amount" class="block text-sm font-medium text-slate-700">Saldo</label>
                                            <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm transition focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100">
                                                <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                                <input id="items_{{ $index }}_balance_amount" name="items[{{ $index }}][balance_amount]" type="text" value="{{ $moneyValue($item['balance_amount'] ?? '') }}" data-tax-money-input data-tax-money="balance" class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @else
                    <section class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-sm font-semibold text-emerald-700">02</div>
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Rincian Pajak</h3>
                                <p class="text-sm text-slate-500">Edit satu data pajak yang dipilih.</p>
                            </div>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="entry_date" class="block text-sm font-medium text-slate-700">Tanggal</label>
                                <input id="entry_date" name="entry_date" type="date" value="{{ old('entry_date', $entry->entry_date ? $entry->entry_date->format('Y-m-d') : $defaultEntryDate) }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                @error('entry_date')
                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="proof_number" class="block text-sm font-medium text-slate-700">Nomor Bukti</label>
                                <input id="proof_number" name="proof_number" type="text" value="{{ old('proof_number', $entry->proof_number) }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                                @error('proof_number')
                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="md:col-span-2">
                                <label for="description" class="block text-sm font-medium text-slate-700">Uraian</label>
                                <textarea id="description" name="description" rows="4" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">{{ old('description', $entry->description) }}</textarea>
                                @error('description')
                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="account_code" class="block text-sm font-medium text-slate-700">Kode Rekening</label>
                                <input id="account_code" name="account_code" type="text" value="{{ old('account_code', $entry->account_code) }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            </div>
                            <div>
                                <label for="account_name" class="block text-sm font-medium text-slate-700">Nama Rekening</label>
                                <select id="account_name" name="account_name" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100">
                                    <option value="">Pilih nama rekening</option>
                                    @foreach ($accountOptions as $option)
                                        <option value="{{ $option }}" @selected(old('account_name', $entry->account_name) === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('account_name')
                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="billing_id" class="block text-sm font-medium text-slate-700">ID Billing</label>
                                <input id="billing_id" name="billing_id" type="text" value="{{ old('billing_id', $entry->billing_id) }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            </div>
                            <div>
                                <label for="ntpn" class="block text-sm font-medium text-slate-700">NTPN</label>
                                <input id="ntpn" name="ntpn" type="text" value="{{ old('ntpn', $entry->ntpn) }}" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100" />
                            </div>
                            @foreach ([
                                ['id' => 'receipt_amount', 'label' => 'Penerimaan', 'value' => old('receipt_amount', $entry->receipt_amount), 'kind' => 'receipt'],
                                ['id' => 'expense_amount', 'label' => 'Pengeluaran', 'value' => old('expense_amount', $entry->expense_amount), 'kind' => 'expense'],
                                ['id' => 'balance_amount', 'label' => 'Saldo', 'value' => old('balance_amount', $entry->balance_amount), 'kind' => 'balance'],
                            ] as $moneyField)
                                <div>
                                    <label for="{{ $moneyField['id'] }}" class="block text-sm font-medium text-slate-700">{{ $moneyField['label'] }}</label>
                                    <div class="mt-2 flex overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-sm transition focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100">
                                        <span class="inline-flex items-center border-r border-slate-200 bg-slate-50 px-4 text-sm text-slate-500">Rp</span>
                                        <input id="{{ $moneyField['id'] }}" name="{{ $moneyField['id'] }}" type="text" value="{{ $moneyValue($moneyField['value']) }}" data-tax-money-input data-tax-money="{{ $moneyField['kind'] }}" class="block w-full px-4 py-3 text-sm text-slate-900 outline-none" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-6">
                    <a href="{{ route('pajak.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-5 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700">
                        Simpan Pajak
                    </button>
                </div>
            </form>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const categoryMode = document.querySelector('[data-category-mode]');
            const existingCategory = document.querySelector('[data-category-existing]');
            const newCategory = document.querySelector('[data-category-new]');
            const wrapper = document.querySelector('[data-tax-items-wrapper]');
            const addButton = document.querySelector('[data-add-tax-row]');
            let nextIndex = wrapper ? wrapper.querySelectorAll('[data-tax-row]').length : 0;

            const toNumber = (value) => Number((value || '').toString().replace(/\D/g, '')) || 0;
            const formatNumber = (value) => Number(value || 0).toLocaleString('id-ID');

            const syncCategoryMode = () => {
                if (!categoryMode || !existingCategory || !newCategory) {
                    return;
                }

                const isNew = categoryMode.value === 'new';
                existingCategory.classList.toggle('hidden', isNew);
                newCategory.classList.toggle('hidden', !isNew);
            };

            const syncBalance = (row) => {
                const receiptInput = row.querySelector('[data-tax-money="receipt"]');
                const expenseInput = row.querySelector('[data-tax-money="expense"]');
                const balanceInput = row.querySelector('[data-tax-money="balance"]');

                if (!receiptInput || !expenseInput || !balanceInput) {
                    return;
                }

                const balance = Math.max(toNumber(receiptInput.value) - toNumber(expenseInput.value), 0);
                balanceInput.value = balance > 0 ? formatNumber(balance) : '';
            };

            const bindMoneyInputs = (row) => {
                row.querySelectorAll('[data-tax-money-input]').forEach((input) => {
                    input.addEventListener('input', () => {
                        const value = toNumber(input.value);
                        input.value = value > 0 ? formatNumber(value) : '';

                        if (input.dataset.taxMoney === 'receipt' || input.dataset.taxMoney === 'expense') {
                            syncBalance(row);
                        }
                    });
                });
            };

            const updateRowControls = () => {
                if (!wrapper) {
                    return;
                }

                const rows = Array.from(wrapper.querySelectorAll('[data-tax-row]'));
                rows.forEach((row, index) => {
                    row.querySelector('[data-tax-row-title]').textContent = `Data Pajak ${index + 1}`;
                    row.querySelector('[data-remove-tax-row]').classList.toggle('hidden', rows.length === 1);
                });
            };

            const resetClonedRow = (row, index) => {
                row.querySelectorAll('input, textarea, select').forEach((field) => {
                    if (field.name) {
                        field.name = field.name.replace(/items\[\d+\]/, `items[${index}]`);
                    }

                    if (field.id) {
                        field.id = field.id.replace(/items_\d+_/, `items_${index}_`);
                    }

                    if (field.tagName === 'SELECT') {
                        field.selectedIndex = 0;
                    } else if (field.type === 'date') {
                        field.value = '{{ $defaultEntryDate }}';
                    } else {
                        field.value = '';
                    }
                });

                row.querySelectorAll('label[for]').forEach((label) => {
                    label.htmlFor = label.htmlFor.replace(/items_\d+_/, `items_${index}_`);
                });
            };

            if (wrapper) {
                wrapper.querySelectorAll('[data-tax-row]').forEach(bindMoneyInputs);
            } else {
                const form = document.querySelector('form');

                if (form) {
                    bindMoneyInputs(form);
                }
            }
            wrapper?.addEventListener('click', (event) => {
                const removeButton = event.target.closest('[data-remove-tax-row]');

                if (!removeButton) {
                    return;
                }

                removeButton.closest('[data-tax-row]').remove();
                updateRowControls();
            });

            addButton?.addEventListener('click', () => {
                const firstRow = wrapper?.querySelector('[data-tax-row]');

                if (!firstRow || !wrapper) {
                    return;
                }

                const row = firstRow.cloneNode(true);
                resetClonedRow(row, nextIndex);
                nextIndex += 1;
                bindMoneyInputs(row);
                wrapper.appendChild(row);
                updateRowControls();
            });

            categoryMode?.addEventListener('change', syncCategoryMode);
            syncCategoryMode();
            updateRowControls();
        });
    </script>
</x-layout>
