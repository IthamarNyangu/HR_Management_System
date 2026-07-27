<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExtendTemporaryAppointmentRequest;
use App\Http\Requests\StoreTemporaryAppointmentRequest;
use App\Http\Requests\UpdateTemporaryAppointmentRequest;
use App\Models\AppointmentStatus;
use App\Models\AppointmentType;
use App\Models\Department;
use App\Models\District;
use App\Models\Employee;
use App\Models\Facility;
use App\Models\JobTitle;
use App\Models\Project;
use App\Models\Province;
use App\Models\StaffPromotion;
use App\Models\TemporaryAppointment;
use App\Services\ActivityLogger;
use App\Services\ReferenceNumberService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TemporaryAppointmentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', TemporaryAppointment::class);

        $appointments = TemporaryAppointment::query()
            ->with(['employee', 'province', 'district', 'facility', 'project', 'department', 'currentJobTitle', 'temporaryJobTitle', 'appointmentType', 'appointmentStatus'])
            ->visibleTo($request->user())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('reference_no', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($query) use ($search) {
                            $query->where('employee_no', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('province_id'), fn ($query) => $query->where('province_id', $request->integer('province_id')))
            ->when($request->filled('district_id'), fn ($query) => $query->where('district_id', $request->integer('district_id')))
            ->when($request->filled('facility_id'), fn ($query) => $query->where('facility_id', $request->integer('facility_id')))
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('current_job_title_id'), fn ($query) => $query->where('current_job_title_id', $request->integer('current_job_title_id')))
            ->when($request->filled('temporary_job_title_id'), fn ($query) => $query->where('temporary_job_title_id', $request->integer('temporary_job_title_id')))
            ->when($request->filled('appointment_status_id'), fn ($query) => $query->where('appointment_status_id', $request->integer('appointment_status_id')))
            ->when($request->filled('start_from'), fn ($query) => $query->whereDate('start_date', '>=', $request->date('start_from')))
            ->when($request->filled('start_to'), fn ($query) => $query->whereDate('start_date', '<=', $request->date('start_to')))
            ->when($request->filled('end_from'), fn ($query) => $query->whereDate('end_date', '>=', $request->date('end_from')))
            ->when($request->filled('end_to'), fn ($query) => $query->whereDate('end_date', '<=', $request->date('end_to')))
            ->when($request->boolean('active'), fn ($query) => $query->whereHas('appointmentStatus', fn ($query) => $query->where('code', 'ACTIVE')))
            ->when($request->boolean('ending_soon'), fn ($query) => $query
                ->whereHas('appointmentStatus', fn ($query) => $query->where('code', 'ACTIVE'))
                ->whereBetween('end_date', [today(), today()->addDays(30)]))
            ->when($request->boolean('expired'), fn ($query) => $query
                ->whereHas('appointmentStatus', fn ($query) => $query->where('code', 'ACTIVE'))
                ->whereDate('end_date', '<', today()))
            ->orderByRaw("
                CASE
                    WHEN appointment_status_id = (SELECT id FROM appointment_statuses WHERE code = 'ACTIVE' LIMIT 1) AND end_date BETWEEN ? AND ? THEN 0
                    WHEN appointment_status_id = (SELECT id FROM appointment_statuses WHERE code = 'ACTIVE' LIMIT 1) THEN 1
                    WHEN appointment_status_id = (SELECT id FROM appointment_statuses WHERE code = 'UPCOMING' LIMIT 1) THEN 2
                    ELSE 3
                END
            ", [today()->toDateString(), today()->addDays(30)->toDateString()])
            ->orderBy('end_date')
            ->paginate(10)
            ->withQueryString();

        return view('temporary-appointments.index', $this->formData($request) + compact('appointments'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', TemporaryAppointment::class);

        $sourcePromotion = $this->sourcePromotionForCreate($request);

        $appointment = $sourcePromotion
            ? $this->appointmentFromPromotion($sourcePromotion)
            : new TemporaryAppointment([
                'province_id' => $request->user()->hasRole('HR Officer') ? $request->user()->province_id : null,
                'start_date' => now()->toDateString(),
            ]);

        return view('temporary-appointments.create', $this->formData($request, $appointment) + compact('appointment', 'sourcePromotion'));
    }

    public function store(StoreTemporaryAppointmentRequest $request, ReferenceNumberService $referenceNumbers, ActivityLogger $activity): RedirectResponse
    {
        $appointment = DB::transaction(function () use ($request, $referenceNumbers) {
            $data = $this->appointmentData($request->validated());
            $data['reference_no'] = $referenceNumbers->generate('TEMP', 'temporary_appointments');
            $data['created_by'] = $request->user()->id;
            $data['updated_by'] = $request->user()->id;

            if ($request->user()->hasRole('HR Officer')) {
                $data['province_id'] = $request->user()->province_id;
            }

            return TemporaryAppointment::create($data);
        });

        if ($request->hasFile('supporting_document')) {
            $this->storeInitialAttachment($request, $appointment, $activity);
        }

        $activity->log(
            'temporary_appointment_created',
            "{$request->user()->name} created temporary appointment {$appointment->reference_no}.",
            $appointment,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('temporary-appointments.show', $appointment)->with('success', 'Temporary appointment created successfully.');
    }

    public function show(TemporaryAppointment $temporaryAppointment): View
    {
        Gate::authorize('view', $temporaryAppointment);

        $temporaryAppointment->load($this->appointmentRelations());

        return view('temporary-appointments.show', ['appointment' => $temporaryAppointment]);
    }

    public function edit(Request $request, TemporaryAppointment $temporaryAppointment): View
    {
        Gate::authorize('update', $temporaryAppointment);

        $temporaryAppointment->load(['employee', 'supervisorEmployee']);

        return view('temporary-appointments.edit', $this->formData($request, $temporaryAppointment) + ['appointment' => $temporaryAppointment]);
    }

    public function update(UpdateTemporaryAppointmentRequest $request, TemporaryAppointment $temporaryAppointment, ActivityLogger $activity): RedirectResponse
    {
        $data = $this->appointmentData($request->validated());
        $data['updated_by'] = $request->user()->id;

        if ($request->user()->hasRole('HR Officer')) {
            $data['province_id'] = $request->user()->province_id;
        }

        $temporaryAppointment->update($data);

        if ($request->hasFile('supporting_document')) {
            $this->storeInitialAttachment($request, $temporaryAppointment->fresh(), $activity);
        }

        $activity->log(
            'temporary_appointment_updated',
            "{$request->user()->name} updated temporary appointment {$temporaryAppointment->reference_no}.",
            $temporaryAppointment,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('temporary-appointments.show', $temporaryAppointment)->with('success', 'Temporary appointment updated successfully.');
    }

    public function extend(ExtendTemporaryAppointmentRequest $request, TemporaryAppointment $temporaryAppointment, ActivityLogger $activity): RedirectResponse
    {
        DB::transaction(function () use ($request, $temporaryAppointment): void {
            $previousEndDate = $temporaryAppointment->end_date;
            $newEndDate = $request->date('new_end_date');

            $temporaryAppointment->extensions()->create([
                'previous_end_date' => $previousEndDate?->toDateString(),
                'new_end_date' => $newEndDate->toDateString(),
                'reason' => $request->input('extension_reason'),
                'comment' => $request->input('extension_comment'),
                'extended_by' => $request->user()->id,
                'extended_at' => now(),
            ]);

            $updates = [
                'end_date' => $newEndDate,
                'updated_by' => $request->user()->id,
            ];

            if ($temporaryAppointment->appointmentStatus?->code === 'COMPLETED') {
                $updates['appointment_status_id'] = $this->statusId($temporaryAppointment->start_date?->isFuture() ? 'UPCOMING' : 'ACTIVE');
                $updates['completed_at'] = null;
            }

            $temporaryAppointment->update($updates);
        });

        $temporaryAppointment->refresh()->load(['employee', 'province', 'facility']);

        $activity->log(
            'temporary_appointment_extended',
            "{$request->user()->name} extended temporary appointment {$temporaryAppointment->reference_no}.",
            $temporaryAppointment,
            [
                'new_end_date' => $request->input('new_end_date'),
                'extension_reason' => $request->input('extension_reason'),
            ],
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('temporary-appointments.show', $temporaryAppointment)->with('success', 'Temporary appointment extended successfully.');
    }

    public function archive(Request $request, TemporaryAppointment $temporaryAppointment, ActivityLogger $activity): RedirectResponse
    {
        Gate::authorize('archive', $temporaryAppointment);

        $temporaryAppointment->update(['archived_by' => $request->user()->id]);
        $temporaryAppointment->delete();

        $activity->log(
            'temporary_appointment_archived',
            "{$request->user()->name} archived temporary appointment {$temporaryAppointment->reference_no}.",
            $temporaryAppointment,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('temporary-appointments.index')->with('success', 'Temporary appointment archived successfully.');
    }

    public function archived(Request $request): View
    {
        Gate::authorize('viewAny', TemporaryAppointment::class);

        $appointments = TemporaryAppointment::onlyTrashed()
            ->with(['employee', 'province', 'temporaryJobTitle', 'appointmentStatus', 'archivedBy'])
            ->visibleTo($request->user())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');

                $query->where(function ($query) use ($search) {
                    $query->where('reference_no', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($query) use ($search) {
                            $query->where('employee_no', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('deleted_at')
            ->paginate(10)
            ->withQueryString();

        return view('temporary-appointments.archived', compact('appointments'));
    }

    public function restore(Request $request, int $id, ActivityLogger $activity): RedirectResponse
    {
        $appointment = TemporaryAppointment::withTrashed()->findOrFail($id);

        Gate::authorize('restore', $appointment);

        $appointment->restore();
        $appointment->update([
            'archived_by' => null,
            'updated_by' => $request->user()->id,
        ]);

        $activity->log(
            'temporary_appointment_restored',
            "{$request->user()->name} restored temporary appointment {$appointment->reference_no}.",
            $appointment,
            user: $request->user(),
            request: $request,
        );

        return redirect()->route('temporary-appointments.show', $appointment)->with('success', 'Temporary appointment restored successfully.');
    }

    /**
     * @return array<string>
     */
    private function appointmentRelations(): array
    {
        return [
            'employee',
            'staffPromotion.promotionType',
            'province',
            'district',
            'facility',
            'project',
            'department',
            'currentJobTitle',
            'temporaryJobTitle',
            'supervisorEmployee',
            'appointmentType',
            'appointmentStatus',
            'createdBy',
            'updatedBy',
            'archivedBy',
            'extensions.extendedBy',
            'attachments.uploadedBy',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, ?TemporaryAppointment $appointment = null): array
    {
        $user = $request->user();
        $selectedEmployee = $this->selectedEmployeeForForm($request, $appointment?->employee_id);
        $selectedSupervisor = $this->selectedEmployeeForForm($request, $appointment?->supervisor_employee_id, 'supervisor_employee_id');

        return [
            'selectedEmployeeOption' => $selectedEmployee ? $this->employeeSearchPayload($selectedEmployee) : null,
            'selectedSupervisorOption' => $selectedSupervisor ? $this->employeeSearchPayload($selectedSupervisor) : null,
            'provinces' => Province::where('is_active', true)
                ->when($user->hasRole('HR Officer'), fn ($query) => $query->whereKey($user->province_id))
                ->orderBy('name')
                ->get(),
            'districts' => District::with('province')->where('is_active', true)->orderBy('name')->get(),
            'facilities' => Facility::with('district')->where('is_active', true)->orderBy('name')->get(),
            'projects' => Project::where('is_active', true)->orderBy('name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'jobTitles' => JobTitle::where('is_active', true)->orderBy('name')->get(),
            'appointmentStatuses' => AppointmentStatus::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function appointmentData(array $data): array
    {
        foreach (['district_id', 'facility_id', 'project_id', 'department_id', 'current_job_title_id', 'reason', 'supervisor_name', 'supervisor_employee_id', 'comment'] as $field) {
            if (array_key_exists($field, $data) && blank($data[$field])) {
                $data[$field] = null;
            }
        }

        $appointmentData = Arr::only($data, [
            'reference_no',
            'staff_promotion_id',
            'employee_id',
            'province_id',
            'district_id',
            'facility_id',
            'project_id',
            'department_id',
            'current_job_title_id',
            'temporary_job_title_id',
            'appointment_status_id',
            'start_date',
            'end_date',
            'reason',
            'supervisor_name',
            'supervisor_employee_id',
            'comment',
            'completed_at',
            'created_by',
            'updated_by',
            'archived_by',
        ]);

        $appointmentData['appointment_type_id'] = $this->defaultAppointmentTypeId();

        return $appointmentData;
    }

    private function defaultAppointmentTypeId(): int
    {
        $type = AppointmentType::where('code', 'INTERIM_ACTING')
            ->orWhere('name', 'Interim / Acting Appointment')
            ->first();

        if (! $type) {
            $type = AppointmentType::create([
                'name' => 'Interim / Acting Appointment',
                'code' => 'INTERIM_ACTING',
                'description' => null,
                'is_active' => true,
            ]);
        } elseif (! $type->is_active) {
            $type->update(['is_active' => true]);
        }

        return (int) $type->id;
    }

    private function sourcePromotionForCreate(Request $request): ?StaffPromotion
    {
        if (! $request->filled('promotion_id')) {
            return null;
        }

        $promotion = StaffPromotion::query()
            ->with(['employee.supervisor', 'promotionType', 'temporaryAppointment'])
            ->visibleTo($request->user())
            ->findOrFail($request->integer('promotion_id'));

        Gate::authorize('view', $promotion);

        abort_unless($promotion->is_acting_promotion, 422, 'Only Acting Promotions can create temporary appointments.');
        abort_if($promotion->temporaryAppointment, 422, 'This Acting Promotion already has a temporary appointment.');

        return $promotion;
    }

    private function appointmentFromPromotion(StaffPromotion $promotion): TemporaryAppointment
    {
        $startDate = $promotion->application_date ?? today();
        $statusCode = $startDate->isFuture() ? 'UPCOMING' : 'ACTIVE';

        return new TemporaryAppointment([
            'staff_promotion_id' => $promotion->id,
            'employee_id' => $promotion->employee_id,
            'province_id' => $promotion->province_id,
            'district_id' => $promotion->district_id,
            'facility_id' => $promotion->facility_id,
            'project_id' => $promotion->project_id,
            'department_id' => $promotion->department_id,
            'current_job_title_id' => $promotion->old_job_title_id,
            'temporary_job_title_id' => $promotion->new_job_title_id,
            'appointment_status_id' => $this->statusId($statusCode),
            'start_date' => $startDate->toDateString(),
            'reason' => "Created from Acting Promotion {$promotion->reference_no}.",
            'supervisor_employee_id' => $promotion->employee?->supervisor_employee_id,
            'supervisor_name' => $promotion->employee?->supervisor?->full_name
                ?? $promotion->employee?->supervisor_name,
        ]);
    }

    private function statusId(string $code): int
    {
        $id = AppointmentStatus::where('code', $code)->value('id')
            ?? AppointmentStatus::where('name', ucfirst(strtolower($code)))->value('id');

        abort_if($id === null, 422, "Appointment status {$code} is not configured.");

        return (int) $id;
    }

    private function selectedEmployeeForForm(Request $request, ?int $fallbackId, string $field = 'employee_id'): ?Employee
    {
        $employeeId = $request->old($field, $fallbackId);

        if (! $employeeId) {
            return null;
        }

        return Employee::query()
            ->visibleTo($request->user())
            ->with(['jobTitle', 'province', 'district', 'facility', 'project', 'department'])
            ->find($employeeId);
    }

    /**
     * @return array<string, mixed>
     */
    private function employeeSearchPayload(Employee $employee): array
    {
        return [
            'id' => $employee->id,
            'text' => $employee->display_name,
            'details' => collect([
                $employee->jobTitle?->name,
                $employee->province?->name,
                $employee->district?->name,
                $employee->facility?->name,
            ])->filter()->implode(' | '),
            'employee_no' => $employee->employee_no,
            'name' => $employee->full_name,
            'email' => $employee->email,
            'job_title' => $employee->jobTitle?->name,
            'province' => $employee->province?->name,
            'district' => $employee->district?->name,
            'facility' => $employee->facility?->name,
            'province_id' => $employee->province_id,
            'district_id' => $employee->district_id,
            'facility_id' => $employee->facility_id,
            'project_id' => $employee->project_id,
            'department_id' => $employee->department_id,
            'job_title_id' => $employee->job_title_id,
        ];
    }

    private function storeInitialAttachment(Request $request, TemporaryAppointment $appointment, ActivityLogger $activity): void
    {
        $file = $request->file('supporting_document');

        if (! $file) {
            return;
        }

        $path = $file->store("temporary-appointments/{$appointment->id}", 'local');

        $attachment = $appointment->attachments()->create([
            'document_type_id' => null,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => basename($path),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize() ?: 0,
            'uploaded_by' => $request->user()->id,
        ]);

        $attachment->setRelation('attachable', $appointment);

        $activity->log(
            'temporary_appointment_attachment_uploaded',
            "{$request->user()->name} uploaded {$attachment->original_filename} to {$appointment->reference_no}.",
            $attachment,
            user: $request->user(),
            request: $request,
        );
    }
}
