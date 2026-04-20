<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ControlEntryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PerjadinController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SavingAllocationController;
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
    Route::delete('/lembar-kontrol/hapus-periode', [ControlEntryController::class, 'destroyPeriod'])->name('lembar-kontrol.destroy-period');
    Route::get('/lembar-kontrol/{controlEntry}/edit', [ControlEntryController::class, 'edit'])->name('lembar-kontrol.edit');
    Route::put('/lembar-kontrol/{controlEntry}', [ControlEntryController::class, 'update'])->name('lembar-kontrol.update');
    Route::delete('/lembar-kontrol/{controlEntry}', [ControlEntryController::class, 'destroy'])->name('lembar-kontrol.destroy');
    Route::get('/dana-saving', [SavingAllocationController::class, 'index'])->name('dana-saving.index');
    Route::get('/dana-saving/tambah', [SavingAllocationController::class, 'create'])->name('dana-saving.create');
    Route::post('/dana-saving', [SavingAllocationController::class, 'store'])->name('dana-saving.store');
    Route::post('/dana-saving/{savingAllocation}/lunasi-hutang', [SavingAllocationController::class, 'settleDebts'])->name('dana-saving.settle-debts');
    Route::get('/dana-saving/{savingAllocation}/edit', [SavingAllocationController::class, 'edit'])->name('dana-saving.edit');
    Route::put('/dana-saving/{savingAllocation}', [SavingAllocationController::class, 'update'])->name('dana-saving.update');
    Route::delete('/dana-saving/{savingAllocation}', [SavingAllocationController::class, 'destroy'])->name('dana-saving.destroy');
});

Route::middleware(['auth', 'role:admin,verifikator'])->group(function () {
    Route::get('/perjadin', [PerjadinController::class, 'index'])->name('perjadin');
    Route::get('/add-perjadin', [PerjadinController::class, 'create'])->name('add-perjadin');
    Route::post('/add-perjadin', [PerjadinController::class, 'store'])->name('add-perjadin.store');
});

Route::middleware(['auth', 'role:admin,bendahara,verifikator'])->group(function () {
    Route::get('/report', [ReportController::class, 'index'])->name('report');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::resource('users', UserController::class)->except('show');
});
