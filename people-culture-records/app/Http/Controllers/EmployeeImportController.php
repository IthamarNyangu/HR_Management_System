<?php

namespace App\Http\Controllers;

use App\Exports\EmployeeImportErrorReportExport;
use App\Exports\EmployeeImportTemplateExport;
use App\Http\Requests\UploadEmployeeImportRequest;
use App\Models\ImportBatch;
use App\Services\ActivityLogger;
use App\Services\Imports\EmployeeImportCommitService;
use App\Services\Imports\EmployeeImportPreviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class EmployeeImportController extends Controller
{
    public function create(): View
    {
        return view('imports.employees.create');
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(new EmployeeImportTemplateExport(), 'employee-import-template.xlsx');
    }

    public function upload(UploadEmployeeImportRequest $request, EmployeeImportPreviewService $previewService): RedirectResponse
    {
        try {
            $batch = $previewService->preview($request->file('file'), $request->user());

            return redirect()
                ->route('imports.employees.preview', $batch)
                ->with('success', 'Import file uploaded and previewed. Review the rows before confirming.');
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'The import file could not be processed. Please check the headings and file contents, then try again.');
        }
    }

    public function preview(Request $request, ImportBatch $importBatch): View
    {
        $this->ensureEmployeeImport($importBatch);
        $this->authorizeBatchAccess($request, $importBatch);

        $rows = $importBatch->rows()
            ->orderBy('row_number')
            ->paginate(25)
            ->withQueryString();

        return view('imports.employees.preview', compact('importBatch', 'rows'));
    }

    public function confirm(Request $request, ImportBatch $importBatch, EmployeeImportCommitService $commitService): RedirectResponse
    {
        $this->ensureEmployeeImport($importBatch);
        $this->authorizeBatchAccess($request, $importBatch);

        if ($importBatch->valid_rows < 1) {
            return back()->with('error', 'There are no valid rows to import.');
        }

        if ($importBatch->isFinal()) {
            return back()->with('error', 'This import batch has already been finalized.');
        }

        $batch = $commitService->commit($importBatch, $request->user());

        return redirect()
            ->route('imports.batches.show', $batch)
            ->with('success', "{$batch->imported_rows} employee records imported successfully.");
    }

    public function cancel(Request $request, ImportBatch $importBatch, ActivityLogger $activityLogger): RedirectResponse
    {
        $this->ensureEmployeeImport($importBatch);
        $this->authorizeBatchAccess($request, $importBatch);

        if (! $importBatch->isFinal()) {
            $importBatch->update(['status' => 'cancelled']);

            $activityLogger->log(
                'import_cancelled',
                "{$request->user()->name} cancelled Employee Import file {$importBatch->original_filename}.",
                $importBatch->fresh(),
                [
                    'import_type' => $importBatch->import_type,
                    'batch_id' => $importBatch->id,
                    'reference_no' => $importBatch->reference_no,
                    'original_filename' => $importBatch->original_filename,
                    'total_rows' => $importBatch->total_rows,
                    'valid_rows' => $importBatch->valid_rows,
                    'invalid_rows' => $importBatch->invalid_rows,
                    'duplicate_rows' => $importBatch->duplicate_rows,
                    'imported_rows' => $importBatch->imported_rows,
                    'province_id' => $request->user()->province_id,
                    'province_name' => $request->user()->province?->name,
                ]
            );
        }

        return redirect()
            ->route('imports.index')
            ->with('success', 'Import batch cancelled.');
    }

    public function errors(Request $request, ImportBatch $importBatch): BinaryFileResponse
    {
        $this->ensureEmployeeImport($importBatch);
        $this->authorizeBatchAccess($request, $importBatch);

        return Excel::download(
            new EmployeeImportErrorReportExport($importBatch),
            "employee-import-errors-{$importBatch->reference_no}.xlsx"
        );
    }

    private function ensureEmployeeImport(ImportBatch $importBatch): void
    {
        abort_unless($importBatch->import_type === 'employees', 404);
    }

    private function authorizeBatchAccess(Request $request, ImportBatch $importBatch): void
    {
        $user = $request->user();

        if ($user->isAdmin() || $user->isHrManager() || (int) $importBatch->uploaded_by === (int) $user->id) {
            return;
        }

        abort(403);
    }
}
