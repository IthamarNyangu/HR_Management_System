<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmploymentStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $employeeQuery = Employee::query()->visibleTo($request->user());
        $activeStatus = EmploymentStatus::where('name', 'Active')->orWhere('code', 'ACTIVE')->first();

        $cards = [
            ['label' => 'Total Employees', 'value' => (clone $employeeQuery)->count()],
            ['label' => 'Active Employees', 'value' => $activeStatus ? (clone $employeeQuery)->where('employment_status_id', $activeStatus->id)->count() : 0],
            ['label' => 'Disciplinary Cases', 'value' => 0],
            ['label' => 'Promotions', 'value' => 0],
            ['label' => 'Relocations', 'value' => 0],
            ['label' => 'Cases Expiring Soon', 'value' => 0],
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
