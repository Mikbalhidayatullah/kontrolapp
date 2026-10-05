# LRFK Full Source Import Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Import and display every main and continuation row from `DATA RAPAT` and `Data OLAHAN`, including all financial, percentage, location, note, cash-plan, and variance values, without changing LRFK Lama or Perjadin links.

**Architecture:** Keep structural and account rows in `lrfk_entries`, add source-row metadata and signed planning fields, and store blank-A:D continuation rows in a dedicated `lrfk_entry_details` table owned by the preceding account. Version-aware rendering and export reproduce each source sheet's column layout, while source-level totals come from the imported Dinas row instead of recalculating through duplicated physical rows.

**Tech Stack:** Laravel 13, PHP 8.4-compatible code, Eloquent, MySQL production database, SQLite in-memory tests, Blade, custom OpenXML XLSX exporter, bundled Python/openpyxl for read-only source extraction.

**Spec:** `docs/superpowers/specs/2026-10-04-lrfk-full-source-import-design.md`

## Global Constraints

- `DATA RAPAT` maps only to dataset version `perubahan`.
- `Data OLAHAN` maps only to dataset version `data_olahan`.
- LRFK Lama and every Perjadin `lrfk_entry_id` remain unchanged.
- Preserve every duplicate physical row and original source order.
- A row with blank A-D and data in later columns belongs to the preceding account.
- Source fields overwrite the matching Perubahan/Data Olahan fields once; IDs and audit ownership remain stable.
- Numeric Excel values remain numeric in database and export.
- Existing role restriction stays `admin,bendahara`.

## Review Focus

- A continuation row before any account must fail the import transaction with an actionable message; Task 3 adds this test.
- Identical duplicate accounts must map by occurrence and retain stable IDs; Task 3 adds this test.
- Negative budget balance, cash plan, and variance values must survive MySQL/SQLite storage and XLSX export; Tasks 1, 3, and 6 test this.
- Blank cells and formula results cached as blank must import without type errors or invented values; Task 2 tests normalization.
- Re-running synchronization must not duplicate details, change main IDs, touch LRFK Lama, or break Perjadin links; Task 3 tests idempotency and isolation.

---

### Task 1: Source Fields and Detail Ownership

**Files:**
- Create: `database/migrations/2026_10_04_110000_add_full_source_fields_to_lrfk.php`
- Create: `app/Models/LrfkEntryDetail.php`
- Modify: `app/Models/LrfkEntry.php`
- Test: `tests/Feature/LrfkFullSourceSchemaTest.php`

**Interfaces:**
- Produces: `LrfkEntry::details(): HasMany` ordered by `sort_order,id`.
- Produces: `LrfkEntryDetail::entry(): BelongsTo`.
- Produces signed integer fields `budget_balance`, `cash_plan_october`, `cash_plan_november`, `cash_plan_december`, `cash_plan_quarter`, and `variance` on main/detail records.
- Produces nullable integer `source_row` on main/detail records.

- [x] **Step 1: Write failing schema and relationship tests**

Add tests named:

- `test_full_source_columns_and_detail_table_exist`
- `test_detail_rows_are_ordered_and_owned_by_their_account`
- `test_signed_source_amounts_accept_negative_values`
- `test_deleting_an_account_cascades_to_its_source_details`

Assert the exact column names above, decimal source percentages, ordered relation output, negative `-56_710_788`, and detail deletion after deleting its account.

- [x] **Step 2: Run the schema tests and verify RED**

Run: `php -d extension=php_pdo_sqlite.dll -d extension=php_sqlite3.dll vendor\bin\phpunit tests\Feature\LrfkFullSourceSchemaTest.php`

Expected: FAIL because the migration, detail table, and model do not exist.

- [x] **Step 3: Add the migration and models**

Create `lrfk_entry_details` with a cascading `lrfk_entry_id` foreign key, `source_row`, `sort_order`, all common LRFK data fields after column D, signed planning fields, timestamps, and an index on `lrfk_entry_id,sort_order`. Add nullable `source_row` and signed planning fields to `lrfk_entries`; widen both percentage columns to preserve eight decimal places. Add fillable/cast definitions and the ordered relation methods.

- [x] **Step 4: Run schema tests and verify GREEN**

Run the command from Step 2.

Expected: all `LrfkFullSourceSchemaTest` tests pass.

- [x] **Step 5: Commit the schema unit**

```bash
git add database/migrations/2026_10_04_110000_add_full_source_fields_to_lrfk.php app/Models/LrfkEntry.php app/Models/LrfkEntryDetail.php tests/Feature/LrfkFullSourceSchemaTest.php
git commit -m "feat: add LRFK source detail storage"
```

### Task 2: Exact Workbook Source Fixtures

**Files:**
- Create: `database/seeders/data/lrfk_full_perubahan.json`
- Create: `database/seeders/data/lrfk_full_data_olahan.json`
- Create: `tests/Feature/LrfkFullSourceFixtureTest.php`

**Interfaces:**
- Produces: JSON arrays of physical rows with `source_row`, `kind` (`entry` or `detail`), `parent_source_row` for details, and normalized application field names from Task 1.
- Consumes: the mapping in the approved spec.

- [x] **Step 1: Write failing fixture-contract tests**

Assert:

- Perubahan fixture has 352 physical rows: 349 `entry`, 3 `detail`.
- Data Olahan fixture has 582 physical rows: 364 `entry`, 218 `detail`.
- Perubahan source row 6 has contract `366_701_836_399`, realization `357_117_006_436`, finance percent `45.60480978`, physical percent `82.27628869`, and variance `9_584_829_963`.
- Perubahan source row 204 is a detail of source row 203 and retains contract/realisasi `56_710_788` plus negative source amounts.
- Data Olahan source row 6 has contract `360_632_883_181`, realization `350_577_909_498`, finance percent `97.21185334`, physical percent `100`, and variance `10_054_973_683`.
- Data Olahan source row 69 is a detail of source row 68 with `contract_number_date = GU IIII` and `implementer = Vivi Irianti`.
- Blank cells normalize to `null` or numeric zero according to the target field contract, never the string `None`.

- [x] **Step 2: Run fixture tests and verify RED**

Run: `php -d extension=php_pdo_sqlite.dll -d extension=php_sqlite3.dll vendor\bin\phpunit tests\Feature\LrfkFullSourceFixtureTest.php`

Expected: FAIL because the full fixtures do not exist.

- [x] **Step 3: Generate fixtures from the workbook**

Use bundled Python/openpyxl in read-only analysis mode. Iterate source rows from row 6, create main records for Dinas/Belanja/Program/Kegiatan/Sub Kegiatan/rekening rows, create detail records for blank A-D rows containing mapped data, and set `parent_source_row` to the preceding account's source row. Preserve whitespace inside multi-line source text while trimming empty edges.

- [x] **Step 4: Run fixture tests and verify GREEN**

Run the command from Step 2.

Expected: all fixture-contract tests pass.

- [x] **Step 5: Commit exact source fixtures**

```bash
git add database/seeders/data/lrfk_full_perubahan.json database/seeders/data/lrfk_full_data_olahan.json tests/Feature/LrfkFullSourceFixtureTest.php
git commit -m "data: add complete LRFK source fixtures"
```

### Task 3: Safe Full-Source Synchronization

**Files:**
- Create: `app/Services/LrfkSourceDatasetImporter.php`
- Create: `database/migrations/2026_10_04_120000_import_full_lrfk_source_data.php`
- Create: `tests/Feature/LrfkFullSourceImportTest.php`
- Modify: `tests/Feature/LrfkChangeDatasetMigrationTest.php`

**Interfaces:**
- Consumes: Task 1 schema and Task 2 fixture contract.
- Produces: `LrfkSourceDatasetImporter::sync(string $version, array $rows): void` as the transactional synchronization boundary used by migration and tests.
- Produces: exact source fields on existing `lrfk_entries` and all `lrfk_entry_details` linked by stable account IDs.

- [x] **Step 1: Write failing import tests**

Add tests against the real importer named:

- `test_import_updates_every_main_source_field_without_changing_ids`
- `test_import_links_all_details_to_the_preceding_account`
- `test_import_preserves_identical_duplicate_account_occurrences`
- `test_import_is_idempotent`
- `test_import_rejects_an_orphan_detail_transactionally`
- `test_import_does_not_touch_old_lrfk_or_perjadin_links`

Assert main/detail counts, representative source values from Task 2, stable pre-import IDs, no duplicate details after a second `up()`, and unchanged old-version values/link IDs. Replace the old zero-contract assertions in `LrfkChangeDatasetMigrationTest` with source totals.

- [x] **Step 2: Run import tests and verify RED**

Run: `php -d extension=php_pdo_sqlite.dll -d extension=php_sqlite3.dll vendor\bin\phpunit tests\Feature\LrfkFullSourceImportTest.php tests\Feature\LrfkChangeDatasetMigrationTest.php`

Expected: FAIL because synchronization is not implemented and old tests still expect zero values.

- [x] **Step 3: Implement the importer and migration**

Implement `LrfkSourceDatasetImporter::sync(string $version, array $rows): void`. Match main records by dataset, structural identity, parent identity, and duplicate occurrence in source order. Update mapped source fields and `source_row` without replacing IDs, audit fields, or parent IDs. Rebuild only `lrfk_entry_details` for the two source datasets inside the same transaction; reject details whose `parent_source_row` does not resolve to a rekening. The migration reads each fixture and calls the importer. Leave `lama` untouched.

- [x] **Step 4: Run import tests and verify GREEN**

Run the command from Step 2.

Expected: all import and prior migration tests pass.

- [x] **Step 5: Commit synchronization**

```bash
git add app/Services/LrfkSourceDatasetImporter.php database/migrations/2026_10_04_120000_import_full_lrfk_source_data.php tests/Feature/LrfkFullSourceImportTest.php tests/Feature/LrfkChangeDatasetMigrationTest.php
git commit -m "feat: import complete LRFK source data"
```

### Task 4: Version-Aware Metrics and Page Data

**Files:**
- Modify: `app/Services/LrfkPerjadinService.php`
- Modify: `app/Http/Controllers/LrfkController.php`
- Modify: `tests/Feature/LrfkDatasetIsolationTest.php`
- Create: `tests/Feature/LrfkSourceMetricsTest.php`

**Interfaces:**
- Consumes: `LrfkEntry::details()` and imported source fields.
- Produces: direct source metrics for `perubahan`/`data_olahan`; keeps linked-account rollups for `lama`.
- Produces: controller collections with eager-loaded details and physical-row summary counts.

- [x] **Step 1: Write failing metrics/controller tests**

Assert:

- Perubahan summary uses Dinas contract/realisasi exactly, not the sum of duplicate accounts.
- Data Olahan summary uses Dinas contract/realisasi exactly.
- A filtered source table retains version-wide source metrics.
- Total Data is 352 for Perubahan and 582 for Data Olahan with no active filter.
- LRFK Lama still rolls paid Perjadin values into account ancestors.

- [x] **Step 2: Run metrics tests and verify RED**

Run: `php -d extension=php_pdo_sqlite.dll -d extension=php_sqlite3.dll vendor\bin\phpunit tests\Feature\LrfkSourceMetricsTest.php tests\Feature\LrfkDatasetIsolationTest.php`

Expected: FAIL on source totals/detail count while existing old rollup remains green.

- [x] **Step 3: Implement version-aware source metrics**

Add a dataset-aware branch in `LrfkPerjadinService::metrics(?Collection $entries = null): array`: source datasets return each entry's imported contract/realisasi/percent values directly; `lama` retains current rollup and linked Perjadin logic. Eager-load ordered details in `LrfkController::filteredEntries()` and count matched details only when their owning account is in the filtered collection.

- [x] **Step 4: Run metrics tests and verify GREEN**

Run the command from Step 2.

Expected: all metrics and isolation tests pass.

- [x] **Step 5: Commit metrics behavior**

```bash
git add app/Services/LrfkPerjadinService.php app/Http/Controllers/LrfkController.php tests/Feature/LrfkSourceMetricsTest.php tests/Feature/LrfkDatasetIsolationTest.php
git commit -m "feat: use authoritative LRFK source metrics"
```

### Task 5: Source-Accurate Web Tables

**Files:**
- Create: `resources/views/lrfk/partials/source-table.blade.php`
- Modify: `resources/views/lrfk/index.blade.php`
- Test: `tests/Feature/LrfkSourceRenderingTest.php`

**Interfaces:**
- Consumes: selected version, entries with ordered details, imported main/detail source fields, and level colors.
- Produces: version-specific physical rows with blank A-D cells for details.

- [x] **Step 1: Write failing rendering tests**

Assert:

- Perubahan page includes `Sisa Pagu Anggaran`, `Oktober`, `November`, `Desember`, `Triwulan IV`, and `Selisih`, but does not insert a Nomor/Tanggal source column.
- Data Olahan page includes `Nomor / Tanggal`, `Lokasi`, `Ket.`, and `Selisih`, but not the cash-plan columns.
- A detail row follows its account and has empty first four cells marked with `data-lrfk-detail`.
- Representative multi-line text, negative amounts, source percentages, and notes appear in their mapped columns.
- LRFK Lama continues rendering its existing table.

- [x] **Step 2: Run rendering tests and verify RED**

Run: `php -d extension=php_pdo_sqlite.dll -d extension=php_sqlite3.dll vendor\bin\phpunit tests\Feature\LrfkSourceRenderingTest.php`

Expected: FAIL because source-specific columns and detail rows are absent.

- [x] **Step 3: Implement the source table partial**

Keep the existing table branch for `lama`. Render `source-table.blade.php` for the other versions, selecting exact headers by version. Render every main row and then its ordered details, keep level colors on main rows, use a restrained white detail style, preserve multi-line text, format signed money values, and show imported percentages rather than recomputing them.

- [x] **Step 4: Run rendering tests and verify GREEN**

Run the command from Step 2.

Expected: all rendering tests pass.

- [x] **Step 5: Commit source table UI**

```bash
git add resources/views/lrfk/index.blade.php resources/views/lrfk/partials/source-table.blade.php tests/Feature/LrfkSourceRenderingTest.php
git commit -m "feat: render complete LRFK source tables"
```

### Task 6: Version-Accurate Excel Export and Final Verification

**Files:**
- Modify: `app/Services/LrfkExcelExporter.php`
- Modify: `app/Http/Controllers/LrfkController.php`
- Modify: `tests/Feature/LrfkDatasetIsolationTest.php`
- Create: `tests/Feature/LrfkSourceExportTest.php`

**Interfaces:**
- Consumes: selected version, complete unfiltered entry collection, ordered details, and source field mapping.
- Produces: old format for `lama`, DATA RAPAT layout for `perubahan`, and Data OLAHAN layout for `data_olahan`.

- [x] **Step 1: Write failing export tests**

Open generated XLSX files with `ZipArchive` and assert:

- Perubahan export includes source headers through Selisih, 352 physical data rows, representative negative values, and source row 204 after row 203.
- Data Olahan export includes Nomor/Tanggal and 582 physical data rows, including source row 69 under row 68.
- Both exports contain all data despite an active page filter.
- Numeric source amounts use numeric cells and percentages use numeric percentage cells.
- LRFK Lama export retains its existing 15-column structure.

- [x] **Step 2: Run export tests and verify RED**

Run: `php -d extension=php_pdo_sqlite.dll -d extension=php_sqlite3.dll vendor\bin\phpunit tests\Feature\LrfkSourceExportTest.php tests\Feature\LrfkDatasetIsolationTest.php`

Expected: FAIL because exporter still emits one fixed layout and no detail rows.

- [x] **Step 3: Implement version-aware export layouts**

Pass `selectedVersion` to `LrfkExcelExporter::export(...)`. Select headers, widths, cell builders, and last-column merge range by version. Emit each detail immediately after its owner with blank A-D cells. Keep all numeric values as number cells, preserve line breaks in text cells, and keep export queries unfiltered.

- [x] **Step 4: Run export tests and verify GREEN**

Run the command from Step 2.

Expected: all source export and isolation tests pass.

- [x] **Step 5: Run complete verification**

Run:

```bash
vendor\bin\pint app\Models\LrfkEntry.php app\Models\LrfkEntryDetail.php app\Services\LrfkSourceDatasetImporter.php app\Services\LrfkPerjadinService.php app\Services\LrfkExcelExporter.php app\Http\Controllers\LrfkController.php database\migrations\2026_10_04_110000_add_full_source_fields_to_lrfk.php database\migrations\2026_10_04_120000_import_full_lrfk_source_data.php tests\Feature\LrfkFullSourceSchemaTest.php tests\Feature\LrfkFullSourceFixtureTest.php tests\Feature\LrfkFullSourceImportTest.php tests\Feature\LrfkSourceMetricsTest.php tests\Feature\LrfkSourceRenderingTest.php tests\Feature\LrfkSourceExportTest.php
php -d extension=php_pdo_sqlite.dll -d extension=php_sqlite3.dll vendor\bin\phpunit
php artisan migrate --force
php artisan view:cache
git diff --check
```

Expected: formatter passes, full suite has zero failures, both MySQL migrations run, Blade cache succeeds, and diff check reports no errors.

- [x] **Step 6: Verify MySQL source totals directly**

Assert local MySQL contains:

- `perubahan`: 349 main rows, 3 details, Dinas contract `366_701_836_399`, realization `357_117_006_436`.
- `data_olahan`: 364 main rows, 218 details, Dinas contract `360_632_883_181`, realization `350_577_909_498`.
- `lama`: unchanged row count and values.
- No Perjadin references a non-`lama` LRFK entry.

- [x] **Step 7: Commit export and verified integration**

```bash
git add app/Services/LrfkExcelExporter.php app/Http/Controllers/LrfkController.php tests/Feature/LrfkSourceExportTest.php tests/Feature/LrfkDatasetIsolationTest.php
git commit -m "feat: export complete LRFK source layouts"
```
