<?php

namespace App\Http\Controllers;

use App\Exports\DisciplinaryCaseImportErrorReportExport;
use App\Exports\DisciplinaryCaseImportTemplateExport;
use App\Http\Requests\UploadDisciplinaryCaseImportRequest;
use App\Models\ImportBatch;
use App\Services\ActivityLogger;
use App\Services\Imports\DisciplinaryCaseImportCommitService;
use App\Services\Imports\DisciplinaryCaseImportPreviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class DisciplinaryCaseImportController extends Controller
{
    public function create(): View
    {
        return view('imports.disciplinary-cases.create');
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(new DisciplinaryCaseImportTemplateExport(), 'disciplinary-cases-import-template.xlsx');
    }

    public function upload(UploadDisciplinaryCaseImportRequest $request, DisciplinaryCaseImportPreviewService $previewService): RedirectResponse
    {
        try {
            $batch = $previewService->preview($request->file('file'), $request->user());

            return redirect()
                ->route('imports.disciplinary-cases.preview', $batch)
                ->with('success', 'Import file uploaded and previewed. Review the disciplinary case rows before confirming.');
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'The disciplinary cases import file could not be processed. Please check the sheet name, headings, and file contents, then try again.');
        }
    }

    public function preview(Request $request, ImportBatch $importBatch): View
    {
        $this->ensureDisciplinaryCaseImport($importBatch);
        $this->authorizeBatchAccess($request, $importBatch);

        $rows = $importBatch->rows()
            ->orderBy('row_number')
            ->paginate(25)
            ->withQueryString();

        return view('imports.disciplinary-cases.preview', compact('importBatch', 'rows'));
    }

    public function confirm(Request $request, ImportBatch $importBatch, DisciplinaryCaseImportCommitService $commitService): RedirectResponse
    {
        $this->ensureDisciplinaryCaseImport($importBatch);
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
            ->with('success', "{$batch->imported_rows} disciplinary case records imported successfully.");
    }

    public function cancel(Request $request, ImportBatch $importBatch, ActivityLogger $activityLogger): RedirectResponse
    {
        $this->ensureDisciplinaryCaseImport($importBatch);
        $this->authorizeBatchAccess($request, $importBatch);

        if (! $importBatch->isFinal()) {
            $importBatch->update(['status' => 'cancelled']);

            $activityLogger->log(
                'import_cancelled',
                "{$request->user()->name} cancelled Disciplinary Cases Import file {$importBatch->original_filename}.",
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
        $this->ensureDisciplinaryCaseImport($importBatch);
        $this->authorizeBatchAccess($request, $importBatch);

        return Excel::download(
            new DisciplinaryCaseImportErrorReportExport($importBatch),
            "disciplinary-cases-import-errors-{$importBatch->reference_no}.xlsx"
        );
    }

    private function ensureDisciplinaryCaseImport(ImportBatch $importBatch): void
    {
        abort_unless($importBatch->import_type === 'disciplinary_cases', 404);
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
