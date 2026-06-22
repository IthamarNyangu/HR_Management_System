<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CaseStatus;
use App\Models\DisciplinaryCase;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\StaffEstablishmentPlan;
use App\Models\StaffPromotion;
use App\Models\StaffRelocation;
use App\Models\AppointmentStatus;
use App\Models\TemporaryAppointment;
use App\Services\StaffEstablishmentMetricsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, StaffEstablishmentMetricsService $establishmentMetrics): View
    {
        $user = $request->user();
        $employeeQuery = Employee::query()->visibleTo($user);
        $caseQuery = DisciplinaryCase::query()->visibleTo($user);
        $promotionQuery = StaffPromotion::query()->visibleTo($user);
        $relocationQuery = StaffRelocation::query()->visibleTo($user);
        $appointmentQuery = TemporaryAppointment::query()->visibleTo($user);
        $jobOpeningQuery = JobOpening::query()->visibleTo($user);
        $jobApplicationQuery = JobApplication::query()->visibleTo($user);

        $activeEmploymentStatus = EmploymentStatus::where('code', 'ACTIVE')->orWhere('name', 'Active')->first();
        $submittedStatus = $this->caseStatus('SUBMITTED');
        $activeCaseStatus = $this->caseStatus('ACTIVE');
        $closedCaseStatus = $this->caseStatus('CLOSED');
        $activeAppointmentStatus = $this->appointmentStatus('ACTIVE');
        $completedAppointmentStatus = $this->appointmentStatus('COMPLETED');

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
            [
                'label' => 'Appointments Ending Soon',
                'value' => $activeAppointmentStatus ? (clone $appointmentQuery)->where('appointment_status_id', $activeAppointmentStatus->id)->whereBetween('end_date', [today(), today()->addDays(30)])->count() : 0,
                'tone' => 'warning',
                'icon' => 'bi-calendar-event',
                'url' => route('temporary-appointments.index', ['ending_soon' => 1]),
            ],
            [
                'label' => 'Expired Appointments',
                'value' => $activeAppointmentStatus ? (clone $appointmentQuery)->where('appointment_status_id', $activeAppointmentStatus->id)->whereDate('end_date', '<', today())->count() : 0,
                'tone' => 'danger',
                'icon' => 'bi-calendar-x',
                'url' => route('temporary-appointments.index', ['expired' => 1]),
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

        $relocationOverview = [
            ['label' => 'Relocations This Year', 'value' => (clone $relocationQuery)->whereYear('effective_date', now()->year)->count()],
            ['label' => 'Relocations This Month', 'value' => (clone $relocationQuery)->whereYear('effective_date', now()->year)->whereMonth('effective_date', now()->month)->count()],
            ['label' => 'Relocation Amount This Year', 'value' => number_format((float) ((clone $relocationQuery)->whereYear('effective_date', now()->year)->sum('relocation_amount') ?? 0), 2)],
        ];

        $temporaryAppointmentOverview = [
            ['label' => 'Active Temporary Appointments', 'value' => $activeAppointmentStatus ? (clone $appointmentQuery)->where('appointment_status_id', $activeAppointmentStatus->id)->count() : 0],
            ['label' => 'Appointments Ending This Month', 'value' => $activeAppointmentStatus ? (clone $appointmentQuery)->where('appointment_status_id', $activeAppointmentStatus->id)->whereYear('end_date', now()->year)->whereMonth('end_date', now()->month)->count() : 0],
        ];

        $latestTemporaryAppointments = TemporaryAppointment::query()
            ->visibleTo($user)
            ->with(['employee', 'temporaryJobTitle'])
            ->latest('start_date')
            ->take(5)
            ->get();

        $recruitmentOverview = [
            ['label' => 'Published External Jobs', 'value' => (clone $jobOpeningQuery)->where('status', JobOpening::STATUS_PUBLISHED)->whereIn('visibility', [JobOpening::VISIBILITY_EXTERNAL, JobOpening::VISIBILITY_BOTH])->whereDate('closing_date', '>=', today())->count()],
            ['label' => 'Internal Jobs', 'value' => (clone $jobOpeningQuery)->whereIn('visibility', [JobOpening::VISIBILITY_INTERNAL, JobOpening::VISIBILITY_BOTH])->count()],
            ['label' => 'Jobs Closing Soon', 'value' => (clone $jobOpeningQuery)->where('status', JobOpening::STATUS_PUBLISHED)->whereBetween('closing_date', [today(), today()->addDays(14)])->count()],
            ['label' => 'Closed Jobs This Month', 'value' => (clone $jobOpeningQuery)->where('status', JobOpening::STATUS_CLOSED)->whereYear('closed_at', now()->year)->whereMonth('closed_at', now()->month)->count()],
            ['label' => 'Awaiting Review', 'value' => (clone $jobApplicationQuery)->where('status', JobApplication::STATUS_SUBMITTED)->count()],
            ['label' => 'Applications Under Review', 'value' => (clone $jobApplicationQuery)->where('status', JobApplication::STATUS_UNDER_REVIEW)->count()],
            ['label' => 'Rejected This Month', 'value' => (clone $jobApplicationQuery)->where('status', JobApplication::STATUS_REJECTED)->whereYear('rejected_at', now()->year)->whereMonth('rejected_at', now()->month)->count()],
            ['label' => 'Received This Week', 'value' => (clone $jobApplicationQuery)->where('submitted_at', '>=', now()->startOfWeek())->count()],
        ];

        $latestEstablishmentPlan = $establishmentMetrics->latestVisiblePlan($user);
        $establishmentSummary = $latestEstablishmentPlan
            ? $establishmentMetrics->summaryForPlan($latestEstablishmentPlan, $user)
            : ['budgeted' => 0, 'filled' => 0, 'vacant' => 0, 'overstaffed' => 0, 'vacancy_rate' => 0.0];

        $staffEstablishmentOverview = [
            ['label' => 'Budgeted Positions', 'value' => $establishmentSummary['budgeted']],
            ['label' => 'Filled Positions', 'value' => $establishmentSummary['filled']],
            ['label' => 'Vacancies', 'value' => $establishmentSummary['vacant']],
            ['label' => 'Vacancy Rate', 'value' => $establishmentSummary['vacancy_rate'].'%'],
        ];

        $latestRelocations = StaffRelocation::query()
            ->visibleTo($user)
            ->with(['employee', 'fromProvince', 'toProvince'])
            ->latest('effective_date')
            ->take(5)
            ->get();

        $recentActivities = ActivityLog::query()
            ->with('user')
            ->visibleTo($user)
            ->latest()
            ->take(3)
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
            'relocationOverview',
            'latestRelocations',
            'temporaryAppointmentOverview',
            'latestTemporaryAppointments',
            'recruitmentOverview',
            'latestEstablishmentPlan',
            'staffEstablishmentOverview',
            'recentActivities',
            'submittedStatus',
            'activeCaseStatus',
        ));
    }

    private function caseStatus(string $code): ?CaseStatus
    {
        return CaseStatus::where('code', $code)->orWhere('name', ucfirst(strtolower($code)))->first();
    }

    private function appointmentStatus(string $code): ?AppointmentStatus
    {
        return AppointmentStatus::where('code', $code)->orWhere('name', ucfirst(strtolower($code)))->first();
    }
}
