<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CaseStatus;
use App\Models\DisciplinaryCase;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\StaffPromotion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $employeeQuery = Employee::query()->visibleTo($user);
        $caseQuery = DisciplinaryCase::query()->visibleTo($user);
        $promotionQuery = StaffPromotion::query()->visibleTo($user);

        $activeEmploymentStatus = EmploymentStatus::where('code', 'ACTIVE')->orWhere('name', 'Active')->first();
        $submittedStatus = $this->caseStatus('SUBMITTED');
        $activeCaseStatus = $this->caseStatus('ACTIVE');
        $closedCaseStatus = $this->caseStatus('CLOSED');

        $needsAttention = [
            [
                'label' => 'Awaiting Approval',
                'value' => $submittedStatus ? (clone $caseQuery)->where('case_status_id', $submittedStatus->id)->count() : 0,
                'tone' => 'primary',
                'icon' => 'bi-inbox',
                'url' => $submittedStatus ? route('disciplinary-cases.index', ['case_status_id' => $submittedStatus->id]) : route('disciplinary-cases.index'),
            ],
            [
                'label' => 'Expiring Within 30 Days',
                'value' => $activeCaseStatus ? (clone $caseQuery)->where('case_status_id', $activeCaseStatus->id)->whereNotNull('expiry_date')->whereDate('expiry_date', '>=', today())->whereDate('expiry_date', '<=', today()->addDays(30))->count() : 0,
                'tone' => 'warning',
                'icon' => 'bi-hourglass-split',
                'url' => route('disciplinary-cases.index', ['expiry_from' => today()->toDateString(), 'expiry_to' => today()->addDays(30)->toDateString()]),
            ],
            [
                'label' => 'Expired Active Cases',
                'value' => $activeCaseStatus ? (clone $caseQuery)->where('case_status_id', $activeCaseStatus->id)->whereNotNull('expiry_date')->whereDate('expiry_date', '<', today())->count() : 0,
                'tone' => 'danger',
                'icon' => 'bi-exclamation-triangle',
                'url' => $activeCaseStatus ? route('disciplinary-cases.index', ['case_status_id' => $activeCaseStatus->id, 'expiry_to' => today()->subDay()->toDateString()]) : route('disciplinary-cases.index'),
            ],
            [
                'label' => 'Active Cases',
                'value' => $activeCaseStatus ? (clone $caseQuery)->where('case_status_id', $activeCaseStatus->id)->count() : 0,
                'tone' => 'success',
                'icon' => 'bi-shield-check',
                'url' => $activeCaseStatus ? route('disciplinary-cases.index', ['case_status_id' => $activeCaseStatus->id]) : route('disciplinary-cases.index'),
            ],
        ];

        $peopleOverview = [
            ['label' => 'Total Employees', 'value' => (clone $employeeQuery)->count()],
            ['label' => 'Active Employees', 'value' => $activeEmploymentStatus ? (clone $employeeQuery)->where('employment_status_id', $activeEmploymentStatus->id)->count() : 0],
            ['label' => 'Archived Employees', 'value' => Employee::onlyTrashed()->visibleTo($user)->count()],
        ];

        $employeesByProvince = Employee::query()
            ->visibleTo($user)
            ->join('provinces', 'employees.province_id', '=', 'provinces.id')
            ->selectRaw('provinces.name as province_name, count(*) as total')
            ->groupBy('provinces.name')
            ->orderBy('provinces.name')
            ->get();

        $caseOverview = [
            ['label' => 'Total Disciplinary Cases', 'value' => (clone $caseQuery)->count()],
            ['label' => 'Active Disciplinary Cases', 'value' => $activeCaseStatus ? (clone $caseQuery)->where('case_status_id', $activeCaseStatus->id)->count() : 0],
            ['label' => 'Closed Disciplinary Cases', 'value' => $closedCaseStatus ? (clone $caseQuery)->where('case_status_id', $closedCaseStatus->id)->count() : 0],
        ];

        $casesByStatus = DisciplinaryCase::query()
            ->visibleTo($user)
            ->join('case_statuses', 'disciplinary_cases.case_status_id', '=', 'case_statuses.id')
            ->selectRaw('case_statuses.name as label, count(*) as total')
            ->groupBy('case_statuses.name')
            ->orderBy('case_statuses.name')
            ->get();

        $casesByOffenceCategory = DisciplinaryCase::query()
            ->visibleTo($user)
            ->leftJoin('offence_categories', 'disciplinary_cases.offence_category_id', '=', 'offence_categories.id')
            ->selectRaw("coalesce(offence_categories.name, 'Unspecified') as label, count(*) as total")
            ->groupBy('offence_categories.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $promotionOverview = [
            ['label' => 'Promotions This Year', 'value' => (clone $promotionQuery)->whereYear('promotion_date', now()->year)->count()],
            ['label' => 'Promotions This Month', 'value' => (clone $promotionQuery)->whereYear('promotion_date', now()->year)->whereMonth('promotion_date', now()->month)->count()],
        ];

        $latestPromotions = StaffPromotion::query()
            ->visibleTo($user)
            ->with(['employee', 'oldJobTitle', 'newJobTitle'])
            ->latest('promotion_date')
            ->take(5)
            ->get();

        $recentActivities = ActivityLog::query()
            ->with('user')
            ->visibleTo($user)
            ->latest()
            ->take(10)
            ->get();

        return view('dashboard', compact(
            'needsAttention',
            'peopleOverview',
            'employeesByProvince',
            'caseOverview',
            'casesByStatus',
            'casesByOffenceCategory',
            'promotionOverview',
            'latestPromotions',
            'recentActivities',
            'submittedStatus',
            'activeCaseStatus',
        ));
    }

    private function caseStatus(string $code): ?CaseStatus
    {
        return CaseStatus::where('code', $code)->orWhere('name', ucfirst(strtolower($code)))->first();
    }
}
