@php($selectedEmployee = old('employee_id', $claim->employee_id))
<div class="row g-3">
 <div class="col-md-6"><label for="employee_id" class="form-label">Employee record</label><select id="employee_id" name="employee_id" class="form-select @error('employee_id') is-invalid @enderror" data-searchable-select data-search-placeholder="Search employee" required><option value="">Select employee</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" data-name="{{ $employee->display_name }}" data-date-of-birth="{{ optional($employee->date_of_birth)->format('Y-m-d') }}" @selected((string)$selectedEmployee===(string)$employee->id)>{{ $employee->display_name }} - {{ $employee->employee_no }}</option>@endforeach</select>@error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
 <div class="col-md-3"><label for="deceased_category" class="form-label">Deceased category</label><select id="deceased_category" name="deceased_category" class="form-select" required><option value="">Select category</option>@foreach($categories as $value=>$label)<option value="{{ $value }}" @selected(old('deceased_category',$claim->deceased_category)===$value)>{{ $label }}</option>@endforeach</select></div>
 <div class="col-md-3"><label for="employee_dependant_id" class="form-label">Registered dependant</label><select id="employee_dependant_id" name="employee_dependant_id" class="form-select @error('employee_dependant_id') is-invalid @enderror"><option value="">Not registered / select</option>@foreach($employees as $employee)@foreach($employee->dependants as $dependant)@php($existingClaim = $dependant->latestFuneralClaim)@php($isCurrentDependant = (string)$claim->employee_dependant_id === (string)$dependant->id)<option value="{{ $dependant->id }}" data-employee="{{ $employee->id }}" data-category="{{ $dependant->category }}" data-name="{{ $dependant->full_name }}" data-date-of-birth="{{ optional($dependant->date_of_birth)->format('Y-m-d') }}" data-existing-claim="{{ $existingClaim?->reference_no }}" @disabled($existingClaim && ! $isCurrentDependant) @selected((string)old('employee_dependant_id',$claim->employee_dependant_id)===(string)$dependant->id)>{{ $dependant->full_name }} ({{ $existingClaim && ! $isCurrentDependant ? 'Claim '.$existingClaim->reference_no.' already registered' : str($dependant->verification_status)->headline() }})</option>@endforeach @endforeach</select>@error('employee_dependant_id')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="form-text">A registered dependant can only have one funeral claim.</div></div>
 <div class="col-md-3"><label for="date_of_death" class="form-label">Date of death</label><input type="date" id="date_of_death" name="date_of_death" class="form-control" value="{{ old('date_of_death',optional($claim->date_of_death)->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}" required></div>
 <div class="col-md-3"><label for="notification_date" class="form-label">Notification date</label><input type="date" id="notification_date" name="notification_date" class="form-control" value="{{ old('notification_date',optional($claim->notification_date)->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}" required></div>
 <div class="col-md-3 manual-field"><label for="deceased_name" class="form-label">Deceased full name</label><input id="deceased_name" name="deceased_name" class="form-control" value="{{ old('deceased_name',$claim->deceased_name) }}"></div>
 <div class="col-md-3 manual-field"><label for="deceased_date_of_birth" class="form-label">Deceased date of birth</label><input type="date" id="deceased_date_of_birth" name="deceased_date_of_birth" class="form-control" value="{{ old('deceased_date_of_birth',optional($claim->deceased_date_of_birth)->format('Y-m-d')) }}"></div>
 <div class="col-md-3 child-field"><label for="gestational_age_weeks" class="form-label">Gestational age (weeks)</label><input type="number" min="1" max="45" step="0.1" id="gestational_age_weeks" name="gestational_age_weeks" class="form-control" value="{{ old('gestational_age_weeks',$claim->gestational_age_weeks) }}"><div class="form-text">Use for a preterm baby claim.</div></div>
 <div class="col-md-4"><label for="assigned_to" class="form-label">Assigned HR user</label><select id="assigned_to" name="assigned_to" class="form-select" data-searchable-select><option value="">Unassigned</option>@foreach($assignees as $user)<option value="{{ $user->id }}" @selected((string)old('assigned_to',$claim->assigned_to)===(string)$user->id)>{{ $user->name }}</option>@endforeach</select></div>
 <div class="col-md-8"><label for="internal_notes" class="form-label">Internal notes</label><textarea id="internal_notes" name="internal_notes" class="form-control" rows="3">{{ old('internal_notes',$claim->internal_notes) }}</textarea></div>
</div>
@foreach($errors->all() as $error)<div class="text-danger small mt-1">{{ $error }}</div>@endforeach
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const employee = document.getElementById('employee_id');
    const category = document.getElementById('deceased_category');
    const dependant = document.getElementById('employee_dependant_id');
    const deceasedName = document.getElementById('deceased_name');
    const deceasedDob = document.getElementById('deceased_date_of_birth');
    const dependantOptions = [...dependant.options];

    const selectedOption = select => [...select.options].find(option => option.value === select.value);

    function syncDeceasedDetails() {
        const source = category.value === 'employee'
            ? selectedOption(employee)
            : selectedOption(dependant);
        const hasRegisteredSource = Boolean(source?.value);

        if (hasRegisteredSource) {
            deceasedName.value = source.dataset.name || '';
            deceasedDob.value = source.dataset.dateOfBirth || '';
        } else if (deceasedName.dataset.autofilled === 'true') {
            deceasedName.value = '';
            deceasedDob.value = '';
        }

        deceasedName.readOnly = hasRegisteredSource;
        deceasedDob.readOnly = hasRegisteredSource;
        deceasedName.dataset.autofilled = hasRegisteredSource ? 'true' : 'false';
        deceasedDob.dataset.autofilled = hasRegisteredSource ? 'true' : 'false';
    }

    function syncForm() {
        const selectedDependant = dependant.value;
        dependant.innerHTML = '';
        dependantOptions.forEach(option => {
            if (!option.value || (option.dataset.employee === employee.value && option.dataset.category === category.value)) {
                dependant.append(option);
            }
        });
        if ([...dependant.options].some(option => option.value === selectedDependant)) {
            dependant.value = selectedDependant;
        }

        document.querySelectorAll('.child-field').forEach(field => field.hidden = category.value !== 'child');
        dependant.disabled = category.value === 'employee' || !employee.value || !category.value;
        if (dependant.disabled) dependant.value = '';
        syncDeceasedDetails();
    }

    employee.addEventListener('change', syncForm);
    category.addEventListener('change', syncForm);
    dependant.addEventListener('change', syncDeceasedDetails);
    syncForm();
});
</script>
@endpush
