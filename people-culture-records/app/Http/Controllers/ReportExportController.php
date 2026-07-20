<?php

namespace App\Http\Controllers;

use App\Exports\ArchivedRecordsReportExport;
use App\Exports\DisciplinaryCasesReportExport;
use App\Exports\EmployeesReportExport;
use App\Exports\ExpiringCasesReportExport;
use App\Exports\StaffPromotionsReportExport;
use App\Exports\StaffRelocationsReportExport;
use App\Services\ActivityLogger;
use App\Services\Reports\ReportQueryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class ReportExportController extends Controller
{
    public function employeesExcel(Request $request, ReportQueryService $reports, ActivityLogger $activity): Response
    {
        return $this->excel('employees', new EmployeesReportExport($reports, $request->user(), $request->query()), $request, $reports, $activity);
    }

    public function employeesPdf(Request $request, ReportQueryService $reports, ActivityLogger $activity): Response
    {
        return $this->pdf('employees', $request, $reports, $activity);
    }

    public function disciplinaryCasesExcel(Request $request, ReportQueryService $reports, ActivityLogger $activity): Response
    {
        return $this->excel('disciplinary-cases', new DisciplinaryCasesReportExport($reports, $request->user(), $request->query()), $request, $reports, $activity);
    }

    public function disciplinaryCasesPdf(Request $request, ReportQueryService $reports, ActivityLogger $activity): Response
    {
        return $this->pdf('disciplinary-cases', $request, $reports, $activity);
    }

    public function promotionsExcel(Request $request, ReportQueryService $reports, ActivityLogger $activity): Response
    {
        return $this->excel('promotions', new StaffPromotionsReportExport($reports, $request->user(), $request->query()), $request, $reports, $activity);
    }

    public function promotionsPdf(Request $request, ReportQueryService $reports, ActivityLogger $activity): Response
    {
        return $this->pdf('promotions', $request, $reports, $activity);
    }

    public function relocationsExcel(Request $request, ReportQueryService $reports, ActivityLogger $activity): Response
    {
        return $this->excel('relocations', new StaffRelocationsReportExport($reports, $request->user(), $request->query()), $request, $reports, $activity);
    }

    public function relocationsPdf(Request $request, ReportQueryService $reports, ActivityLogger $activity): Response
    {
        return $this->pdf('relocations', $request, $reports, $activity);
    }

    public function expiringCasesExcel(Request $request, ReportQueryService $reports, ActivityLogger $activity): Response
    {
        $filters = $this->expiringFilters($request);

        return $this->excel('expiring-cases', new ExpiringCasesReportExport($reports, $request->user(), $filters), $request, $reports, $activity, $filters);
    }

    public function expiringCasesPdf(Request $request, ReportQueryService $reports, ActivityLogger $activity): Response
    {
        return $this->pdf('expiring-cases', $request, $reports, $activity, $this->expiringFilters($request));
    }

    public function archivedRecordsExcel(Request $request, ReportQueryService $reports, ActivityLogger $activity): Response
    {
        return $this->excel('archived-records', new ArchivedRecordsReportExport($reports, $request->user(), $request->query()), $request, $reports, $activity);
    }

    public function archivedRecordsPdf(Request $request, ReportQueryService $reports, ActivityLogger $activity): Response
    {
        return $this->pdf('archived-records', $request, $reports, $activity);
    }

    private function excel(string $type, $export, Request $request, ReportQueryService $reports, ActivityLogger $activity, ?array $filters = null): Response
    {
        Gate::authorize('export-reports');

        $filters ??= $request->query();
        $report = $reports->definition($type);

        if ($reports->rows($type, $request->user(), $filters)->isEmpty()) {
            return redirect()
                ->route($report['route'], $filters)
                ->with('error', 'There are no records to export for the current report filters.');
        }

        $this->logExport($type, 'Excel', $request, $activity, $filters);

        return Excel::download($export, str($report['title'])->slug()->append('-')->append(now()->format('Ymd-His'))->append('.xlsx')->toString());
    }

    private function pdf(string $type, Request $request, ReportQueryService $reports, ActivityLogger $activity, ?array $filters = null): Response
    {
        Gate::authorize('export-reports');

        $filters ??= $request->query();
        $report = $reports->definition($type);
        $rows = $reports->rows($type, $request->user(), $filters);

        if ($rows->isEmpty()) {
            return redirect()
                ->route($report['route'], $filters)
                ->with('error', 'There are no records to export for the current report filters.');
        }

        $this->logExport($type, 'PDF', $request, $activity, $filters);

        return Pdf::loadView("reports.pdf.{$type}", [
            'report' => $report,
            'rows' => $rows,
            'generatedBy' => $request->user(),
            'generatedAt' => now(),
            'filterSummary' => $reports->filterSummary($filters),
        ])->setPaper('a4', 'landscape')->download(str($report['title'])->slug()->append('-')->append(now()->format('Ymd-His'))->append('.pdf')->toString());
    }

    /**
     * @return array<string, mixed>
     */
    private function expiringFilters(Request $request): array
    {
        return array_merge($request->query(), [
            'expiry_from' => $request->input('expiry_from', today()->toDateString()),
            'expiry_to' => $request->input('expiry_to', today()->addDays(30)->toDateString()),
        ]);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function logExport(string $type, string $format, Request $request, ActivityLogger $activity, array $filters): void
    {
        $reportName = $this->displayName($type);

        $activity->log(
            'report_exported_'.strtolower($format),
            "{$request->user()->name} exported {$reportName} to {$format}.",
            null,
            [
                'report_type' => $type,
                'format' => strtolower($format),
                'filters' => $filters,
                'exported_by' => $request->user()->email,
                'province_id' => $request->user()->province_id,
                'province_name' => $request->user()->province?->name,
            ],
            user: $request->user(),
            request: $request,
        );
    }

    private function displayName(string $type): string
    {
        return match ($type) {
            'employees' => 'Employee List Report',
            'disciplinary-cases' => 'Disciplinary Cases Report',
            'promotions' => 'Staff Promotions Report',
            'relocations' => 'Staff Relocations Report',
            'expiring-cases' => 'Expiring Disciplinary Cases Report',
            'archived-records' => 'Archived Records Report',
            default => 'Report',
        };
    }
}
