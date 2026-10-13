<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeDependantRequest;
use App\Http\Requests\UpdateEmployeeDependantRequest;
use App\Models\Employee;
use App\Models\EmployeeDependant;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmployeeDependantController extends Controller
{
    public function create(Employee $employee): View
    {
        Gate::authorize('create', [EmployeeDependant::class, $employee]);

        return view('employee-dependants.create', [
            'employee' => $employee,
            'dependant' => new EmployeeDependant([
                'verification_status' => EmployeeDependant::VERIFICATION_UNVERIFIED,
                'is_active' => true,
            ]),
        ] + $this->formData());
    }

    public function store(StoreEmployeeDependantRequest $request, Employee $employee, ActivityLogger $activity): RedirectResponse
    {
        $data = $this->dependantData($request->validated(), $request);
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $dependant = $employee->dependants()->create($data);

        $activity->log(
            'employee_dependant_created',
            "{$request->user()->name} added a dependant for {$employee->display_name}.",
            $dependant,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('employees.show', $employee)->with('success', 'Dependant added successfully.');
    }

    public function edit(Employee $employee, EmployeeDependant $dependant): View
    {
        $this->ensureOwnership($employee, $dependant);
        Gate::authorize('update', $dependant);

        return view('employee-dependants.edit', compact('employee', 'dependant') + $this->formData());
    }

    public function update(UpdateEmployeeDependantRequest $request, Employee $employee, EmployeeDependant $dependant, ActivityLogger $activity): RedirectResponse
    {
        $this->ensureOwnership($employee, $dependant);

        $data = $this->dependantData($request->validated(), $request, $dependant);
        $data['updated_by'] = $request->user()->id;
        $dependant->update($data);

        $activity->log(
            'employee_dependant_updated',
            "{$request->user()->name} updated a dependant for {$employee->display_name}.",
            $dependant,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('employees.show', $employee)->with('success', 'Dependant updated successfully.');
    }

    public function archive(Request $request, Employee $employee, EmployeeDependant $dependant, ActivityLogger $activity): RedirectResponse
    {
        $this->ensureOwnership($employee, $dependant);
        Gate::authorize('archive', $dependant);

        $dependant->update(['archived_by' => $request->user()->id]);
        $dependant->delete();

        $activity->log(
            'employee_dependant_archived',
            "{$request->user()->name} archived a dependant for {$employee->display_name}.",
            $dependant,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('employees.show', $employee)->with('success', 'Dependant archived successfully.');
    }

    public function archived(Request $request, Employee $employee): View
    {
        Gate::authorize('view', $employee);
        Gate::authorize('viewAny', EmployeeDependant::class);

        $dependants = $employee->dependants()
            ->onlyTrashed()
            ->with('archivedBy')
            ->latest('deleted_at')
            ->paginate(10);

        return view('employee-dependants.archived', compact('employee', 'dependants'));
    }

    public function restore(Request $request, Employee $employee, int $dependant, ActivityLogger $activity): RedirectResponse
    {
        $record = $employee->dependants()->onlyTrashed()->findOrFail($dependant);
        Gate::authorize('restore', $record);

        $record->restore();
        $record->update([
            'archived_by' => null,
            'updated_by' => $request->user()->id,
        ]);

        $activity->log(
            'employee_dependant_restored',
            "{$request->user()->name} restored a dependant for {$employee->display_name}.",
            $record,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('employees.show', $employee)->with('success', 'Dependant restored successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'categories' => EmployeeDependant::categories(),
            'relationshipDetails' => EmployeeDependant::relationshipDetails(),
            'verificationStatuses' => EmployeeDependant::verificationStatuses(),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function dependantData(array $validated, Request $request, ?EmployeeDependant $dependant = null): array
    {
        if ($validated['verification_status'] === EmployeeDependant::VERIFICATION_VERIFIED) {
            if ($dependant?->verification_status !== EmployeeDependant::VERIFICATION_VERIFIED) {
                $validated['verified_by'] = $request->user()->id;
                $validated['verified_at'] = now();
            }
        } else {
            $validated['verified_by'] = null;
            $validated['verified_at'] = null;
        }

        return $validated;
    }

    private function ensureOwnership(Employee $employee, EmployeeDependant $dependant): void
    {
        abort_unless($dependant->employee_id === $employee->id, 404);
    }
}
