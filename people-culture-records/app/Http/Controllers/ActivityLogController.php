<?php

namespace App\Http\Controllers;

use App\Models\Province;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ActivityLogQueryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request, ActivityLogQueryService $logs): View
    {
        $filters = $request->query();
        $activities = $logs->query($request->user(), $filters)
            ->paginate(10)
            ->withQueryString();

        return view('activity-logs.index', [
            'activities' => $activities,
            'actions' => $logs->actions($request->user()),
            'modules' => $logs->modules(),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'provinces' => Province::query()
                ->when(! $request->user()->isAdmin() && ! $request->user()->isHrManager(), fn ($query) => $query->whereKey($request->user()->province_id))
                ->orderBy('name')
                ->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    public function exportPdf(Request $request, ActivityLogQueryService $logs, ActivityLogger $activity): Response
    {
        $filters = $request->query();
        $activities = $logs->query($request->user(), $filters)
            ->limit(1000)
            ->get();

        $activity->log(
            'audit_logs_exported_pdf',
            "{$request->user()->name} exported Audit Logs to PDF.",
            null,
            [
                'filters' => $filters,
                'format' => 'pdf',
                'province_id' => $request->user()->province_id,
                'province_name' => $request->user()->province?->name,
            ],
            user: $request->user(),
            request: $request,
        );

        return Pdf::loadView('activity-logs.pdf', [
            'activities' => $activities,
            'generatedBy' => $request->user(),
            'generatedAt' => now(),
            'filterSummary' => $logs->filterSummary($filters),
        ])->setPaper('a4', 'landscape')->download('audit-logs-'.now()->format('Ymd-His').'.pdf');
    }
}
