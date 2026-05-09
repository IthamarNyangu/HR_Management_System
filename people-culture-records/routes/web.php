<?php

use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Support\MasterDataRegistry;
use Illuminate\Support\Facades\Route;

$masterDataTypes = implode('|', array_map(fn (string $type) => preg_quote($type, '/'), array_keys(MasterDataRegistry::all())));

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'active'])->group(function () use ($masterDataTypes) {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('can:access-dashboard')
        ->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () use ($masterDataTypes) {
        Route::middleware('can:manage-master-data')->group(function () use ($masterDataTypes) {
            Route::get('/', [MasterDataController::class, 'index'])->name('index');
            Route::get('/master-data', [MasterDataController::class, 'index'])->name('master-data.index');
            Route::get('/master-data/{type}', [MasterDataController::class, 'records'])->where('type', $masterDataTypes)->name('master-data.records');
            Route::get('/master-data/{type}/create', [MasterDataController::class, 'create'])->where('type', $masterDataTypes)->name('master-data.create');
            Route::post('/master-data/{type}', [MasterDataController::class, 'store'])->where('type', $masterDataTypes)->name('master-data.store');
            Route::get('/master-data/{type}/{id}/edit', [MasterDataController::class, 'edit'])->where('type', $masterDataTypes)->whereNumber('id')->name('master-data.edit');
            Route::put('/master-data/{type}/{id}', [MasterDataController::class, 'update'])->where('type', $masterDataTypes)->whereNumber('id')->name('master-data.update');
            Route::patch('/master-data/{type}/{id}/toggle-status', [MasterDataController::class, 'toggleStatus'])->where('type', $masterDataTypes)->whereNumber('id')->name('master-data.toggle-status');
        });

        Route::middleware('can:manage-users')->group(function () {
            Route::resource('users', UserController::class)->except(['show', 'destroy']);
            Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        });
    });
});
