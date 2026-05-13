<?php

namespace App\Http\Controllers;

use App\Services\Reports\ReportQueryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function index(ReportQueryService $reports): View
    {
        Gate::authorize('view-reports');

        return view('reports.index', [
            'reports' => $reports->definitions(),
        ]);
    }

    public function employees(Request $request, ReportQueryService $reports): View
    {
        return $this->show('employees', $request, $reports);
    }

    public function disciplinaryCases(Request $request, ReportQueryService $reports): View
    {
        return $this->show('disciplinary-cases', $request, $reports);
    }

    public function promotions(Request $request, ReportQueryService $reports): View
    {
        return $this->show('promotions', $request, $reports);
    }

    public function relocations(Request $request, ReportQueryService $reports): View
    {
        return $this->show('relocations', $request, $reports);
    }

    public function expiringCases(Request $request, ReportQueryService $reports): View
    {
        $request->merge([
            'expiry_from' => $request->input('expiry_from', today()->toDateString()),
            'expiry_to' => $request->input('expiry_to', today()->addDays(30)->toDateString()),
        ]);

        return $this->show('expiring-cases', $request, $reports);
    }

    public function archivedRecords(Request $request, ReportQueryService $reports): View
    {
        return $this->show('archived-records', $request, $reports);
    }

    private function show(string $type, Request $request, ReportQueryService $reports): View
    {
        Gate::authorize('view-reports');

        return view("reports.{$type}", [
            'type' => $type,
            'report' => $reports->definition($type),
            'rows' => $reports->paginate($type, $request->user(), $request->query()),
            'options' => $reports->options(),
            'filterSummary' => $reports->filterSummary($request->query()),
        ]);
    }
}
