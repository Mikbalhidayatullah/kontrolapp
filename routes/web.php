<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ControlEntryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocalTransportSbuController;
use App\Http\Controllers\LrfkController;
use App\Http\Controllers\PerjadinController;
use App\Http\Controllers\PerjadinPaymentController;
use App\Http\Controllers\SavingAllocationController;
use App\Http\Controllers\TaxEntryController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/dasborapp', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth', 'role:admin,bendahara'])->group(function () {
    Route::get('/lembar-kontrol', [ControlEntryController::class, 'index'])->name('lembar-kontrol');
    Route::get('/add-data-kontrol', [ControlEntryController::class, 'create'])->name('add-data-kontrol');
    Route::post('/add-data-kontrol', [ControlEntryController::class, 'store'])->name('add-data-kontrol.store');
    Route::get('/lembar-kontrol/{controlEntry}/bukti', [ControlEntryController::class, 'showProof'])->name('lembar-kontrol.proof');
    Route::delete('/lembar-kontrol/hapus-periode', [ControlEntryController::class, 'destroyPeriod'])->name('lembar-kontrol.destroy-period');
    Route::post('/lembar-kontrol/{controlEntry}/duplicate', [ControlEntryController::class, 'duplicate'])->name('lembar-kontrol.duplicate');
    Route::get('/lembar-kontrol/{controlEntry}/edit', [ControlEntryController::class, 'edit'])->name('lembar-kontrol.edit');
    Route::put('/lembar-kontrol/{controlEntry}', [ControlEntryController::class, 'update'])->name('lembar-kontrol.update');
    Route::delete('/lembar-kontrol/{controlEntry}', [ControlEntryController::class, 'destroy'])->name('lembar-kontrol.destroy');
    Route::get('/dana-saving', [SavingAllocationController::class, 'index'])->name('dana-saving.index');
    Route::get('/dana-saving/tambah', [SavingAllocationController::class, 'create'])->name('dana-saving.create');
    Route::post('/dana-saving', [SavingAllocationController::class, 'store'])->name('dana-saving.store');
    Route::get('/dana-saving/pengurangan/tambah', [SavingAllocationController::class, 'createReduction'])->name('dana-saving.reductions.create');
    Route::post('/dana-saving/pengurangan', [SavingAllocationController::class, 'storeReduction'])->name('dana-saving.reductions.store');
    Route::post('/dana-saving/{savingAllocation}/lunasi-hutang', [SavingAllocationController::class, 'settleDebts'])->name('dana-saving.settle-debts');
    Route::get('/dana-saving/pengurangan/{savingReduction}/edit', [SavingAllocationController::class, 'editReduction'])->name('dana-saving.reductions.edit');
    Route::put('/dana-saving/pengurangan/{savingReduction}', [SavingAllocationController::class, 'updateReduction'])->name('dana-saving.reductions.update');
    Route::delete('/dana-saving/pengurangan/{savingReduction}', [SavingAllocationController::class, 'destroyReduction'])->name('dana-saving.reductions.destroy');
    Route::get('/dana-saving/{savingAllocation}/edit', [SavingAllocationController::class, 'edit'])->name('dana-saving.edit');
    Route::put('/dana-saving/{savingAllocation}', [SavingAllocationController::class, 'update'])->name('dana-saving.update');
    Route::delete('/dana-saving/{savingAllocation}', [SavingAllocationController::class, 'destroy'])->name('dana-saving.destroy');
});

Route::middleware(['auth', 'role:admin,bendahara,verifikator'])->group(function () {
    Route::get('/pajak', [TaxEntryController::class, 'index'])->name('pajak.index');
    Route::get('/pajak/export/excel', [TaxEntryController::class, 'exportExcel'])->name('pajak.export.xlsx');
    Route::get('/pajak/tambah', [TaxEntryController::class, 'create'])->name('pajak.create');
    Route::post('/pajak', [TaxEntryController::class, 'store'])->name('pajak.store');
    Route::patch('/pajak/kategori/rename', [TaxEntryController::class, 'renameCategory'])->name('pajak.categories.rename');
    Route::get('/pajak/tu/{taxTuEntry}/edit', [TaxEntryController::class, 'editTu'])->name('pajak.tu.edit');
    Route::put('/pajak/tu/{taxTuEntry}', [TaxEntryController::class, 'updateTu'])->name('pajak.tu.update');
    Route::delete('/pajak/tu/{taxTuEntry}', [TaxEntryController::class, 'destroyTu'])->name('pajak.tu.destroy');
    Route::get('/pajak/{taxEntry}/edit', [TaxEntryController::class, 'edit'])->name('pajak.edit');
    Route::put('/pajak/{taxEntry}', [TaxEntryController::class, 'update'])->name('pajak.update');
    Route::delete('/pajak/{taxEntry}', [TaxEntryController::class, 'destroy'])->name('pajak.destroy');
});

Route::middleware(['auth', 'role:admin,bendahara,verifikator'])->group(function () {
    Route::get('/perjadin', [PerjadinController::class, 'index'])->name('perjadin');
    Route::get('/perjadin/export/excel', [PerjadinController::class, 'exportExcel'])->name('perjadin.export.xlsx');
    Route::get('/perjadin/export/bpk-excel', [PerjadinController::class, 'exportBpkExcel'])->middleware('role:admin,bendahara')->name('perjadin.export.bpk.xlsx');
    Route::get('/perjadin/halaman-bayar', [PerjadinPaymentController::class, 'index'])->middleware('role:admin,bendahara')->name('perjadin-payments.index');
    Route::post('/perjadin/halaman-bayar/export/excel', [PerjadinPaymentController::class, 'exportExcel'])->middleware('role:admin,bendahara')->name('perjadin-payments.export.xlsx');
    Route::get('/add-perjadin', [PerjadinController::class, 'create'])->name('add-perjadin');
    Route::post('/add-perjadin', [PerjadinController::class, 'store'])->name('add-perjadin.store');
    Route::post('/perjadin/{perjadinEntry}/bayar', [PerjadinController::class, 'togglePayment'])->name('perjadin.payment.toggle');
    Route::get('/perjadin/{perjadinEntry}', [PerjadinController::class, 'show'])->name('perjadin.show');
    Route::post('/perjadin/{perjadinEntry}/duplicate', [PerjadinController::class, 'duplicate'])->name('perjadin.duplicate');
    Route::get('/perjadin/{perjadinEntry}/detail/pdf', [PerjadinController::class, 'downloadDetailPdf'])->name('perjadin.detail.pdf');
    Route::post('/perjadin/{perjadinEntry}/kwitansi/pdf', [PerjadinController::class, 'downloadReceiptPdf'])->name('perjadin.receipt.pdf');
    Route::get('/perjadin/{perjadinEntry}/edit', [PerjadinController::class, 'edit'])->name('perjadin.edit');
    Route::put('/perjadin/{perjadinEntry}', [PerjadinController::class, 'update'])->name('perjadin.update');
    Route::delete('/perjadin/{perjadinEntry}', [PerjadinController::class, 'destroy'])->name('perjadin.destroy');
    Route::get('/perjadin/{perjadinEntry}/lampiran/{attachment}', [PerjadinController::class, 'showAttachment'])->name('perjadin.attachments.show');
    Route::get('/sbu-transport-lokal', [LocalTransportSbuController::class, 'index'])->name('local-transport-sbus.index');
});

Route::middleware(['auth', 'role:admin,bendahara,verifikator'])->group(function () {
    Route::get('/lrfk', [LrfkController::class, 'index'])->name('lrfk.index');
    Route::get('/lrfk/tambah', [LrfkController::class, 'create'])->name('lrfk.create');
    Route::post('/lrfk', [LrfkController::class, 'store'])->name('lrfk.store');
    Route::get('/lrfk/{lrfkEntry}/edit', [LrfkController::class, 'edit'])->name('lrfk.edit');
    Route::put('/lrfk/{lrfkEntry}', [LrfkController::class, 'update'])->name('lrfk.update');
    Route::delete('/lrfk/{lrfkEntry}', [LrfkController::class, 'destroy'])->name('lrfk.destroy');
});

Route::middleware(['auth', 'role:admin,bendahara'])->group(function () {
    Route::get('/sbu-transport-lokal/tambah', [LocalTransportSbuController::class, 'create'])->name('local-transport-sbus.create');
    Route::post('/sbu-transport-lokal', [LocalTransportSbuController::class, 'store'])->name('local-transport-sbus.store');
    Route::get('/sbu-transport-lokal/{localTransportSbu}/edit', [LocalTransportSbuController::class, 'edit'])->name('local-transport-sbus.edit');
    Route::put('/sbu-transport-lokal/{localTransportSbu}', [LocalTransportSbuController::class, 'update'])->name('local-transport-sbus.update');
    Route::delete('/sbu-transport-lokal/{localTransportSbu}', [LocalTransportSbuController::class, 'destroy'])->name('local-transport-sbus.destroy');
    Route::get('/sbu-transport-lokal/{type}/{id}/edit', [LocalTransportSbuController::class, 'editEntry'])->name('local-transport-sbus.entries.edit');
    Route::put('/sbu-transport-lokal/{type}/{id}', [LocalTransportSbuController::class, 'updateEntry'])->name('local-transport-sbus.entries.update');
    Route::delete('/sbu-transport-lokal/{type}/{id}', [LocalTransportSbuController::class, 'destroyEntry'])->name('local-transport-sbus.entries.destroy');
  });

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('users', UserController::class)->except('show');
});
