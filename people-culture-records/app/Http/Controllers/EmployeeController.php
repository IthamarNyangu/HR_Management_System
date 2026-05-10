<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Department;
use App\Models\District;
use App\Models\Employee;
use App\Models\EmploymentStatus;
use App\Models\Facility;
use App\Models\JobTitle;
use App\Models\Project;
use App\Models\Province;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Employee::class);

        $employees = Employee::query()
            ->with(['province', 'district', 'facility', 'project', 'jobTitle', 'employmentStatus'])
            ->visibleTo($request->user())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('employee_no', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('province_id'), fn ($query) => $query->where('province_id', $request->integer('province_id')))
            ->when($request->filled('district_id'), fn ($query) => $query->where('district_id', $request->integer('district_id')))
            ->when($request->filled('facility_id'), fn ($query) => $query->where('facility_id', $request->integer('facility_id')))
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('job_title_id'), fn ($query) => $query->where('job_title_id', $request->integer('job_title_id')))
            ->when($request->filled('employment_status_id'), fn ($query) => $query->where('employment_status_id', $request->integer('employment_status_id')))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10)
            ->withQueryString();

        return view('employees.index', $this->formData($request) + compact('employees'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Employee::class);

        $employee = new Employee([
            'province_id' => $request->user()->hasRole('HR Officer') ? $request->user()->province_id : null,
        ]);

        return view('employees.create', $this->formData($request) + compact('employee'));
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $data = $this->employeeData($request->validated());
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        if ($request->user()->hasRole('HR Officer')) {
            $data['province_id'] = $request->user()->province_id;
        }

        $employee = Employee::create($data);

        return redirect()->route('employees.show', $employee)->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee): View
    {
        Gate::authorize('view', $employee);

        $employee->load([
            'project',
            'department',
            'jobTitle',
            'province',
            'district',
            'facility',
            'employmentStatus',
            'createdBy',
            'updatedBy',
        ]);

        return view('employees.show', compact('employee'));
    }

    public function edit(Request $request, Employee $employee): View
    {
        Gate::authorize('update', $employee);

        return view('employees.edit', $this->formData($request) + compact('employee'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $data = $this->employeeData($request->validated());
        $data['updated_by'] = $request->user()->id;

        if ($request->user()->hasRole('HR Officer')) {
            $data['province_id'] = $request->user()->province_id;
        }

        $employee->update($data);

        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated successfully.');
    }

    public function archive(Request $request, Employee $employee): RedirectResponse
    {
        Gate::authorize('archive', $employee);

        $employee->update(['archived_by' => $request->user()->id]);
        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Employee archived successfully.');
    }

    public function archived(Request $request): View
    {
        Gate::authorize('viewAny', Employee::class);

        $employees = Employee::onlyTrashed()
            ->with(['province', 'district', 'facility', 'project', 'jobTitle', 'employmentStatus', 'archivedBy'])
            ->visibleTo($request->user())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('employee_no', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        return view('employees.archived', compact('employees'));
    }

    public function restore(Request $request, int $id): RedirectResponse
    {
        $employee = Employee::withTrashed()->findOrFail($id);

        Gate::authorize('restore', $employee);

        $employee->restore();
        $employee->update([
            'archived_by' => null,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('employees.show', $employee)->with('success', 'Employee restored successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request): array
    {
        $user = $request->user();

        return [
            'provinces' => Province::where('is_active', true)
                ->when($user->hasRole('HR Officer'), fn ($query) => $query->whereKey($user->province_id))
                ->orderBy('name')
                ->get(),
            'districts' => District::with('province')->where('is_active', true)->orderBy('name')->get(),
            'facilities' => Facility::with('district')->where('is_active', true)->orderBy('name')->get(),
            'projects' => Project::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'jobTitles' => JobTitle::where('is_active', true)->orderBy('name')->get(),
            'employmentStatuses' => EmploymentStatus::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function employeeData(array $data): array
    {
        foreach (['project_id', 'department_id', 'job_title_id', 'facility_id', 'employment_status_id', 'gender', 'date_of_birth', 'national_id', 'email', 'phone', 'hire_date', 'supervisor_name', 'notes'] as $field) {
            if (array_key_exists($field, $data) && blank($data[$field])) {
                $data[$field] = null;
            }
        }

        return Arr::only($data, [
            'employee_no',
            'first_name',
            'last_name',
            'gender',
            'date_of_birth',
            'national_id',
            'email',
            'phone',
            'project_id',
            'department_id',
            'job_title_id',
            'province_id',
            'district_id',
            'facility_id',
            'employment_status_id',
            'hire_date',
            'supervisor_name',
            'notes',
            'created_by',
            'updated_by',
            'archived_by',
        ]);
    }
}
