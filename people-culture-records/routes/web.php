<?php

use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisciplinaryCaseAttachmentController;
use App\Http\Controllers\DisciplinaryCaseController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StaffPromotionAttachmentController;
use App\Http\Controllers\StaffPromotionController;
use App\Http\Controllers\StaffRelocationAttachmentController;
use App\Http\Controllers\StaffRelocationController;
use App\Support\MasterDataRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

$masterDataTypes = implode('|', array_map(fn (string $type) => preg_quote($type, '/'), array_keys(MasterDataRegistry::all())));

Route::redirect('/', '/dashboard');

Route::post('/sign-out', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('app.logout');

Route::middleware(['auth', 'active'])->group(function () use ($masterDataTypes) {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('can:access-dashboard')
        ->name('dashboard');

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->middleware('can:access-dashboard')
        ->name('activity-logs.index');

    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.mark-all-read');

    Route::get('/employees/archived', [EmployeeController::class, 'archived'])->name('employees.archived');
    Route::patch('/employees/{employee}/archive', [EmployeeController::class, 'archive'])->name('employees.archive');
    Route::patch('/employees/{id}/restore', [EmployeeController::class, 'restore'])->whereNumber('id')->name('employees.restore');
    Route::resource('employees', EmployeeController::class)->except(['destroy']);

    Route::get('/disciplinary-cases/archived', [DisciplinaryCaseController::class, 'archived'])->name('disciplinary-cases.archived');
    Route::patch('/disciplinary-cases/{disciplinary_case}/submit', [DisciplinaryCaseController::class, 'submit'])->name('disciplinary-cases.submit');
    Route::patch('/disciplinary-cases/{disciplinary_case}/approve', [DisciplinaryCaseController::class, 'approve'])->name('disciplinary-cases.approve');
    Route::patch('/disciplinary-cases/{disciplinary_case}/close', [DisciplinaryCaseController::class, 'close'])->name('disciplinary-cases.close');
    Route::patch('/disciplinary-cases/{disciplinary_case}/archive', [DisciplinaryCaseController::class, 'archive'])->name('disciplinary-cases.archive');
    Route::post('/disciplinary-cases/{disciplinary_case}/attachments', [DisciplinaryCaseAttachmentController::class, 'store'])->name('disciplinary-cases.attachments.store');
    Route::get('/disciplinary-cases/{disciplinary_case}/attachments/{attachment}/download', [DisciplinaryCaseAttachmentController::class, 'download'])->name('disciplinary-cases.attachments.download');
    Route::delete('/disciplinary-cases/{disciplinary_case}/attachments/{attachment}', [DisciplinaryCaseAttachmentController::class, 'delete'])->name('disciplinary-cases.attachments.delete');
    Route::patch('/disciplinary-cases/{id}/restore', [DisciplinaryCaseController::class, 'restore'])->whereNumber('id')->name('disciplinary-cases.restore');
    Route::resource('disciplinary-cases', DisciplinaryCaseController::class)
        ->parameters(['disciplinary-cases' => 'disciplinary_case'])
        ->except(['destroy']);

    Route::get('/staff-promotions/archived', [StaffPromotionController::class, 'archived'])->name('staff-promotions.archived');
    Route::patch('/staff-promotions/{staff_promotion}/archive', [StaffPromotionController::class, 'archive'])->name('staff-promotions.archive');
    Route::post('/staff-promotions/{staff_promotion}/attachments', [StaffPromotionAttachmentController::class, 'store'])->name('staff-promotions.attachments.store');
    Route::get('/staff-promotions/{staff_promotion}/attachments/{attachment}/download', [StaffPromotionAttachmentController::class, 'download'])->name('staff-promotions.attachments.download');
    Route::delete('/staff-promotions/{staff_promotion}/attachments/{attachment}', [StaffPromotionAttachmentController::class, 'delete'])->name('staff-promotions.attachments.delete');
    Route::patch('/staff-promotions/{id}/restore', [StaffPromotionController::class, 'restore'])->whereNumber('id')->name('staff-promotions.restore');
    Route::resource('staff-promotions', StaffPromotionController::class)
        ->parameters(['staff-promotions' => 'staff_promotion'])
        ->except(['destroy']);

    Route::get('/staff-relocations/archived', [StaffRelocationController::class, 'archived'])->name('staff-relocations.archived');
    Route::patch('/staff-relocations/{staff_relocation}/archive', [StaffRelocationController::class, 'archive'])->name('staff-relocations.archive');
    Route::post('/staff-relocations/{staff_relocation}/attachments', [StaffRelocationAttachmentController::class, 'store'])->name('staff-relocations.attachments.store');
    Route::get('/staff-relocations/{staff_relocation}/attachments/{attachment}/download', [StaffRelocationAttachmentController::class, 'download'])->name('staff-relocations.attachments.download');
    Route::delete('/staff-relocations/{staff_relocation}/attachments/{attachment}', [StaffRelocationAttachmentController::class, 'delete'])->name('staff-relocations.attachments.delete');
    Route::patch('/staff-relocations/{id}/restore', [StaffRelocationController::class, 'restore'])->whereNumber('id')->name('staff-relocations.restore');
    Route::resource('staff-relocations', StaffRelocationController::class)
        ->parameters(['staff-relocations' => 'staff_relocation'])
        ->except(['destroy']);

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
