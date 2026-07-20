<?php

use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisciplinaryCaseAttachmentController;
use App\Http\Controllers\DisciplinaryCaseController;
use App\Http\Controllers\DisciplinaryCaseImportController;
use App\Http\Controllers\EmployeeBulkActionController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeImportController;
use App\Http\Controllers\EmployeeSearchController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrganisationChartController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\PublicCareerController;
use App\Http\Controllers\PublicJobApplicationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\StaffEstablishmentController;
use App\Http\Controllers\Api\PublicJobOpeningController;
use App\Http\Controllers\Recruitment\JobApplicationController;
use App\Http\Controllers\Recruitment\JobApplicationDocumentController;
use App\Http\Controllers\Recruitment\JobOpeningController;
use App\Http\Controllers\StaffPromotionAttachmentController;
use App\Http\Controllers\StaffPromotionController;
use App\Http\Controllers\StaffRelocationAttachmentController;
use App\Http\Controllers\StaffRelocationController;
use App\Http\Controllers\TemporaryAppointmentAttachmentController;
use App\Http\Controllers\TemporaryAppointmentController;
use App\Support\MasterDataRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

$masterDataTypes = implode('|', array_map(fn (string $type) => preg_quote($type, '/'), array_keys(MasterDataRegistry::all())));

Route::redirect('/', '/dashboard');

Route::get('/careers', [PublicCareerController::class, 'index'])->name('careers.index');
Route::get('/careers/{jobOpening:slug}/apply', [PublicJobApplicationController::class, 'create'])->name('careers.apply');
Route::post('/careers/{jobOpening:slug}/apply', [PublicJobApplicationController::class, 'store'])->middleware('throttle:5,1')->name('careers.apply.store');
Route::get('/careers/{jobOpening:slug}/announcement/pdf', [PublicCareerController::class, 'downloadAnnouncementPdf'])->name('careers.announcement.pdf');
Route::post('/careers/{jobOpening:slug}/share', [PublicCareerController::class, 'share'])->middleware('throttle:5,1')->name('careers.share');
Route::get('/careers/{jobOpening:slug}', [PublicCareerController::class, 'show'])->name('careers.show');
Route::get('/applications/withdraw', [PublicJobApplicationController::class, 'withdrawalRequest'])->name('applications.withdraw.request');
Route::post('/applications/withdraw', [PublicJobApplicationController::class, 'sendWithdrawalLink'])->middleware('throttle:5,1')->name('applications.withdraw.link');
Route::get('/applications/{jobApplication}/withdraw/{token}', [PublicJobApplicationController::class, 'withdrawShow'])->middleware('signed')->name('applications.withdraw.show');
Route::post('/applications/{jobApplication}/withdraw/{token}', [PublicJobApplicationController::class, 'withdrawConfirm'])->middleware('signed')->name('applications.withdraw.confirm');
Route::get('/api/careers/jobs', [PublicJobOpeningController::class, 'index'])->name('api.careers.jobs.index');
Route::get('/api/careers/jobs/{slug}', [PublicJobOpeningController::class, 'show'])->name('api.careers.jobs.show');

Route::post('/sign-out', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('app.logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/password/change', [PasswordChangeController::class, 'edit'])->name('password.change');
    Route::put('/password/change', [PasswordChangeController::class, 'update'])->name('password.change.update');
});

Route::middleware(['auth', 'active', 'password.changed'])->group(function () use ($masterDataTypes) {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('can:access-dashboard')
        ->name('dashboard');

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->middleware('can:view-audit-logs')
        ->name('activity-logs.index');
    Route::get('/activity-logs/export/pdf', [ActivityLogController::class, 'exportPdf'])
        ->middleware('can:view-audit-logs')
        ->name('activity-logs.export.pdf');

    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.mark-all-read');

    Route::prefix('imports')->name('imports.')->middleware('can:view-imports')->group(function () {
        Route::get('/', [ImportController::class, 'index'])->name('index');
        Route::get('/batches/{importBatch}', [ImportController::class, 'showBatch'])->name('batches.show');

        Route::middleware('can:import-employees')->group(function () {
            Route::get('/employees', [EmployeeImportController::class, 'create'])->name('employees.create');
            Route::get('/employees/template', [EmployeeImportController::class, 'template'])->name('employees.template');
            Route::post('/employees/upload', [EmployeeImportController::class, 'upload'])->name('employees.upload');
            Route::get('/employees/{importBatch}/preview', [EmployeeImportController::class, 'preview'])->name('employees.preview');
            Route::get('/employees/{importBatch}/errors', [EmployeeImportController::class, 'errors'])->name('employees.errors');
            Route::post('/employees/{importBatch}/confirm', [EmployeeImportController::class, 'confirm'])->name('employees.confirm');
            Route::patch('/employees/{importBatch}/cancel', [EmployeeImportController::class, 'cancel'])->name('employees.cancel');
        });

        Route::middleware('can:import-disciplinary-cases')->group(function () {
            Route::get('/disciplinary-cases', [DisciplinaryCaseImportController::class, 'create'])->name('disciplinary-cases.create');
            Route::get('/disciplinary-cases/template', [DisciplinaryCaseImportController::class, 'template'])->name('disciplinary-cases.template');
            Route::post('/disciplinary-cases/upload', [DisciplinaryCaseImportController::class, 'upload'])->name('disciplinary-cases.upload');
            Route::get('/disciplinary-cases/{importBatch}/preview', [DisciplinaryCaseImportController::class, 'preview'])->name('disciplinary-cases.preview');
            Route::get('/disciplinary-cases/{importBatch}/errors', [DisciplinaryCaseImportController::class, 'errors'])->name('disciplinary-cases.errors');
            Route::post('/disciplinary-cases/{importBatch}/confirm', [DisciplinaryCaseImportController::class, 'confirm'])->name('disciplinary-cases.confirm');
            Route::patch('/disciplinary-cases/{importBatch}/cancel', [DisciplinaryCaseImportController::class, 'cancel'])->name('disciplinary-cases.cancel');
        });
    });

    Route::prefix('reports')->name('reports.')->middleware('can:view-reports')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/employees', [ReportController::class, 'employees'])->name('employees');
        Route::get('/disciplinary-cases', [ReportController::class, 'disciplinaryCases'])->name('disciplinary-cases');
        Route::get('/promotions', [ReportController::class, 'promotions'])->name('promotions');
        Route::get('/relocations', [ReportController::class, 'relocations'])->name('relocations');
        Route::get('/expiring-cases', [ReportController::class, 'expiringCases'])->name('expiring-cases');
        Route::get('/archived-records', [ReportController::class, 'archivedRecords'])->name('archived-records');

        Route::get('/employees/export/excel', [ReportExportController::class, 'employeesExcel'])->middleware('can:export-reports')->name('employees.export.excel');
        Route::get('/employees/export/pdf', [ReportExportController::class, 'employeesPdf'])->middleware('can:export-reports')->name('employees.export.pdf');
        Route::get('/disciplinary-cases/export/excel', [ReportExportController::class, 'disciplinaryCasesExcel'])->middleware('can:export-reports')->name('disciplinary-cases.export.excel');
        Route::get('/disciplinary-cases/export/pdf', [ReportExportController::class, 'disciplinaryCasesPdf'])->middleware('can:export-reports')->name('disciplinary-cases.export.pdf');
        Route::get('/promotions/export/excel', [ReportExportController::class, 'promotionsExcel'])->middleware('can:export-reports')->name('promotions.export.excel');
        Route::get('/promotions/export/pdf', [ReportExportController::class, 'promotionsPdf'])->middleware('can:export-reports')->name('promotions.export.pdf');
        Route::get('/relocations/export/excel', [ReportExportController::class, 'relocationsExcel'])->middleware('can:export-reports')->name('relocations.export.excel');
        Route::get('/relocations/export/pdf', [ReportExportController::class, 'relocationsPdf'])->middleware('can:export-reports')->name('relocations.export.pdf');
        Route::get('/expiring-cases/export/excel', [ReportExportController::class, 'expiringCasesExcel'])->middleware('can:export-reports')->name('expiring-cases.export.excel');
        Route::get('/expiring-cases/export/pdf', [ReportExportController::class, 'expiringCasesPdf'])->middleware('can:export-reports')->name('expiring-cases.export.pdf');
        Route::get('/archived-records/export/excel', [ReportExportController::class, 'archivedRecordsExcel'])->middleware('can:export-reports')->name('archived-records.export.excel');
        Route::get('/archived-records/export/pdf', [ReportExportController::class, 'archivedRecordsPdf'])->middleware('can:export-reports')->name('archived-records.export.pdf');
    });

    Route::get('/employees/search', EmployeeSearchController::class)->name('employees.search');

    Route::get('/staff-establishment/archived', [StaffEstablishmentController::class, 'archived'])->name('staff-establishment.archived');
    Route::get('/staff-establishment/{staff_establishment_plan}/export/excel', [StaffEstablishmentController::class, 'exportExcel'])->name('staff-establishment.export.excel');
    Route::get('/staff-establishment/{staff_establishment_plan}/export/pdf', [StaffEstablishmentController::class, 'exportPdf'])->name('staff-establishment.export.pdf');
    Route::patch('/staff-establishment/{staff_establishment_plan}/archive', [StaffEstablishmentController::class, 'archive'])->name('staff-establishment.archive');
    Route::patch('/staff-establishment/{id}/restore', [StaffEstablishmentController::class, 'restore'])->whereNumber('id')->name('staff-establishment.restore');
    Route::resource('staff-establishment', StaffEstablishmentController::class)
        ->parameters(['staff-establishment' => 'staff_establishment_plan'])
        ->except(['destroy']);

    Route::get('/recruitment', [JobOpeningController::class, 'dashboard'])->name('recruitment.index');
    Route::get('/recruitment/applications', [JobApplicationController::class, 'index'])->name('recruitment.applications.index');
    Route::patch('/recruitment/applications/{jobApplication}/review', [JobApplicationController::class, 'updateReview'])->name('recruitment.applications.update-review');
    Route::patch('/recruitment/applications/{jobApplication}/status', [JobApplicationController::class, 'updateStatus'])->name('recruitment.applications.update-status');
    Route::post('/recruitment/applications/{jobApplication}/notes', [JobApplicationController::class, 'addNote'])->name('recruitment.applications.add-note');
    Route::patch('/recruitment/applications/{jobApplication}/reject', [JobApplicationController::class, 'reject'])->name('recruitment.applications.reject');
    Route::post('/recruitment/applications/{jobApplication}/email', [JobApplicationController::class, 'sendEmail'])->name('recruitment.applications.send-email');
    Route::get('/recruitment/applications/{jobApplication}', [JobApplicationController::class, 'show'])->name('recruitment.applications.show');
    Route::get('/recruitment/applications/{jobApplication}/documents/{document}/view', [JobApplicationDocumentController::class, 'view'])->name('recruitment.applications.documents.view');
    Route::get('/recruitment/applications/{jobApplication}/documents/{document}/download', [JobApplicationDocumentController::class, 'download'])->name('recruitment.applications.documents.download');
    Route::get('/recruitment/job-openings/archived', [JobOpeningController::class, 'archived'])->name('recruitment.job-openings.archived');
    Route::patch('/recruitment/job-openings/{job_opening}/publish', [JobOpeningController::class, 'publish'])->name('recruitment.job-openings.publish');
    Route::patch('/recruitment/job-openings/{job_opening}/close', [JobOpeningController::class, 'close'])->name('recruitment.job-openings.close');
    Route::patch('/recruitment/job-openings/{job_opening}/cancel', [JobOpeningController::class, 'cancel'])->name('recruitment.job-openings.cancel');
    Route::patch('/recruitment/job-openings/{job_opening}/prepare-readvertising', [JobOpeningController::class, 'prepareForReadvertising'])->name('recruitment.job-openings.prepare-readvertising');
    Route::post('/recruitment/job-openings/{job_opening}/notify-previous-applicants', [JobOpeningController::class, 'notifyPreviousApplicants'])->name('recruitment.job-openings.notify-previous-applicants');
    Route::patch('/recruitment/job-openings/{job_opening}/archive', [JobOpeningController::class, 'archive'])->name('recruitment.job-openings.archive');
    Route::get('/recruitment/job-openings/{job_opening}/announcement/pdf', [JobOpeningController::class, 'downloadAnnouncementPdf'])->name('recruitment.job-openings.announcement.pdf');
    Route::patch('/recruitment/job-openings/{id}/restore', [JobOpeningController::class, 'restore'])->whereNumber('id')->name('recruitment.job-openings.restore');
    Route::resource('recruitment/job-openings', JobOpeningController::class)
        ->parameters(['job-openings' => 'job_opening'])
        ->names('recruitment.job-openings')
        ->except(['destroy']);

    Route::get('/employees/reporting-structure', [OrganisationChartController::class, 'reportingStructure'])->name('employees.reporting-structure');
    Route::post('/employees/reporting-structure/link-line-managers', [OrganisationChartController::class, 'linkLineManagers'])->name('employees.reporting-structure.link-line-managers');
    Route::post('/organisation-chart/link-line-managers', [OrganisationChartController::class, 'linkLineManagers'])->name('organisation-chart.link-line-managers');
    Route::get('/organisation-chart/archived', [OrganisationChartController::class, 'archived'])->name('organisation-chart.archived');
    Route::get('/organisation-chart/{organisation_chart}/designer', [OrganisationChartController::class, 'designer'])->name('organisation-chart.designer');
    Route::patch('/organisation-chart/{organisation_chart}/layout', [OrganisationChartController::class, 'updateLayout'])->name('organisation-chart.layout.update');
    Route::post('/organisation-chart/{organisation_chart}/nodes/{node}/duplicate', [OrganisationChartController::class, 'duplicateNode'])->name('organisation-chart.nodes.duplicate');
    Route::delete('/organisation-chart/{organisation_chart}/nodes/{node}', [OrganisationChartController::class, 'destroyNode'])->name('organisation-chart.nodes.destroy');
    Route::patch('/organisation-chart/{organisation_chart}/archive', [OrganisationChartController::class, 'archive'])->name('organisation-chart.archive');
    Route::patch('/organisation-chart/{id}/restore', [OrganisationChartController::class, 'restore'])->whereNumber('id')->name('organisation-chart.restore');
    Route::resource('organisation-chart', OrganisationChartController::class)
        ->parameters(['organisation-chart' => 'organisation_chart'])
        ->except(['destroy']);
    Route::get('/employees/archived', [EmployeeController::class, 'archived'])->name('employees.archived');
    Route::post('/employees/bulk-action', [EmployeeBulkActionController::class, 'handle'])->name('employees.bulk-action');
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

    Route::get('/temporary-appointments/archived', [TemporaryAppointmentController::class, 'archived'])->name('temporary-appointments.archived');
    Route::patch('/temporary-appointments/{temporary_appointment}/archive', [TemporaryAppointmentController::class, 'archive'])->name('temporary-appointments.archive');
    Route::patch('/temporary-appointments/{temporary_appointment}/extend', [TemporaryAppointmentController::class, 'extend'])->name('temporary-appointments.extend');
    Route::post('/temporary-appointments/{temporary_appointment}/attachments', [TemporaryAppointmentAttachmentController::class, 'store'])->name('temporary-appointments.attachments.store');
    Route::get('/temporary-appointments/{temporary_appointment}/attachments/{attachment}/download', [TemporaryAppointmentAttachmentController::class, 'download'])->name('temporary-appointments.attachments.download');
    Route::delete('/temporary-appointments/{temporary_appointment}/attachments/{attachment}', [TemporaryAppointmentAttachmentController::class, 'delete'])->name('temporary-appointments.attachments.delete');
    Route::patch('/temporary-appointments/{id}/restore', [TemporaryAppointmentController::class, 'restore'])->whereNumber('id')->name('temporary-appointments.restore');
    Route::resource('temporary-appointments', TemporaryAppointmentController::class)
        ->parameters(['temporary-appointments' => 'temporary_appointment'])
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
            Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        });
    });
});
