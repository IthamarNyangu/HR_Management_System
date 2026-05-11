<?php

namespace App\Http\Controllers;

use App\Models\CaseStatus;
use App\Models\DisciplinaryCase;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $employeeQuery = Employee::query()->visibleTo($request->user());
        $disciplinaryCaseQuery = DisciplinaryCase::query()->visibleTo($request->user());
        $activeStatus = EmploymentStatus::where('name', 'Active')->orWhere('code', 'ACTIVE')->first();
        $activeCaseStatus = CaseStatus::where('code', 'ACTIVE')->orWhere('name', 'Active')->first();
        $closedCaseStatus = CaseStatus::where('code', 'CLOSED')->orWhere('name', 'Closed')->first();

        $cards = [
            ['label' => 'Total Employees', 'value' => (clone $employeeQuery)->count()],
            ['label' => 'Active Employees', 'value' => $activeStatus ? (clone $employeeQuery)->where('employment_status_id', $activeStatus->id)->count() : 0],
            ['label' => 'Total Disciplinary Cases', 'value' => (clone $disciplinaryCaseQuery)->count()],
            ['label' => 'Active Disciplinary Cases', 'value' => $activeCaseStatus ? (clone $disciplinaryCaseQuery)->where('case_status_id', $activeCaseStatus->id)->count() : 0],
            ['label' => 'Closed Disciplinary Cases', 'value' => $closedCaseStatus ? (clone $disciplinaryCaseQuery)->where('case_status_id', $closedCaseStatus->id)->count() : 0],
            ['label' => 'Cases Expiring Soon', 'value' => $activeCaseStatus ? (clone $disciplinaryCaseQuery)->where('case_status_id', $activeCaseStatus->id)->whereNotNull('expiry_date')->whereDate('expiry_date', '>=', today())->whereDate('expiry_date', '<=', today()->addDays(30))->count() : 0],
            ['label' => 'Expired Cases Not Closed', 'value' => $closedCaseStatus ? (clone $disciplinaryCaseQuery)->where('case_status_id', '!=', $closedCaseStatus->id)->whereNotNull('expiry_date')->whereDate('expiry_date', '<', today())->count() : 0],
            ['label' => 'Promotions', 'value' => 0],
            ['label' => 'Relocations', 'value' => 0],
        ];

        if ($request->user()->isAdmin() || $request->user()->isHrManager()) {
            $cards[] = [
                'label' => 'Archived Employees',
                'value' => Employee::onlyTrashed()->visibleTo($request->user())->count(),
            ];
        }

        return view('dashboard', compact('cards'));
    }
}
