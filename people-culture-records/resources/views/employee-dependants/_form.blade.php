@php
    $selectedCategory = old('category', $dependant->category);
    $selectedRelationship = old('relationship_detail', $dependant->relationship_detail);
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label for="category" class="form-label">Dependant category <span class="text-danger">*</span></label>
        <select id="category" name="category" class="form-select @error('category') is-invalid @enderror" required>
            <option value="">Select category</option>
            @foreach ($categories as $value => $label)
                <option value="{{ $value }}" @selected($selectedCategory === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label id="relationship_detail_label" for="relationship_detail" class="form-label">Relationship <span class="text-danger">*</span></label>
        <select id="relationship_detail" name="relationship_detail" data-selected="{{ $selectedRelationship }}" class="form-select @error('relationship_detail') is-invalid @enderror" required>
            @if ($selectedCategory === App\Models\EmployeeDependant::CATEGORY_SPOUSE)
                <option value="spouse" selected>Spouse</option>
            @elseif ($selectedCategory === App\Models\EmployeeDependant::CATEGORY_CHILD)
                <option value="">Select child type</option>
                @foreach (collect($relationshipDetails)->except('spouse') as $value => $label)
                    <option value="{{ $value }}" @selected($selectedRelationship === $value)>{{ $label }}</option>
                @endforeach
            @else
                <option value="">Select a dependant category first</option>
            @endif
        </select>
        @error('relationship_detail')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-8">
        <label for="full_name" class="form-label">Full name <span class="text-danger">*</span></label>
        <input id="full_name" name="full_name" type="text" value="{{ old('full_name', $dependant->full_name) }}" class="form-control @error('full_name') is-invalid @enderror" maxlength="255" required>
        @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="gender" class="form-label">Gender</label>
        <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror">
            <option value="">Select gender</option>
            @foreach (['Female', 'Male', 'Other'] as $gender)
                <option value="{{ $gender }}" @selected(old('gender', $dependant->gender) === $gender)>{{ $gender }}</option>
            @endforeach
        </select>
        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="date_of_birth" class="form-label">Date of birth</label>
        <input id="date_of_birth" name="date_of_birth" type="date" max="{{ today()->toDateString() }}" value="{{ old('date_of_birth', $dependant->date_of_birth?->toDateString()) }}" class="form-control @error('date_of_birth') is-invalid @enderror">
        @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="national_id" class="form-label">NRC / identification number</label>
        <input id="national_id" name="national_id" type="text" value="{{ old('national_id', $dependant->national_id) }}" class="form-control @error('national_id') is-invalid @enderror" maxlength="255">
        @error('national_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="verification_status" class="form-label">Verification status <span class="text-danger">*</span></label>
        <select id="verification_status" name="verification_status" class="form-select @error('verification_status') is-invalid @enderror" required>
            @foreach ($verificationStatuses as $value => $label)
                <option value="{{ $value }}" @selected(old('verification_status', $dependant->verification_status) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('verification_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check mb-2">
            <input type="hidden" name="is_active" value="0">
            <input id="is_active" name="is_active" type="checkbox" value="1" class="form-check-input @error('is_active') is-invalid @enderror" @checked((bool) old('is_active', $dependant->is_active ?? true))>
            <label for="is_active" class="form-check-label">Active dependant</label>
            @error('is_active')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-12">
        <label for="internal_notes" class="form-label">Internal notes</label>
        <textarea id="internal_notes" name="internal_notes" rows="4" maxlength="5000" class="form-control @error('internal_notes') is-invalid @enderror">{{ old('internal_notes', $dependant->internal_notes) }}</textarea>
        <div class="form-text">Visible only to authorized People & Culture users.</div>
        @error('internal_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const category = document.getElementById('category');
            const relationship = document.getElementById('relationship_detail');
            const relationshipLabel = document.getElementById('relationship_detail_label');
            const childRelationships = @json(collect($relationshipDetails)->except('spouse'));

            function addOption(value, label, selected = false) {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                option.selected = selected;
                relationship.appendChild(option);
            }

            function rebuildRelationships(preserveSelection = false) {
                const previousValue = preserveSelection ? relationship.value : relationship.dataset.selected;
                relationship.innerHTML = '';

                if (category.value === 'spouse') {
                    relationshipLabel.firstChild.textContent = 'Relationship ';
                    addOption('spouse', 'Spouse', true);
                    return;
                }

                if (category.value === 'child') {
                    relationshipLabel.firstChild.textContent = 'Child type ';
                    addOption('', 'Select child type');

                    Object.entries(childRelationships).forEach(([value, label]) => {
                        addOption(value, label, previousValue === value);
                    });

                    return;
                }

                relationshipLabel.firstChild.textContent = 'Relationship ';
                addOption('', 'Select a dependant category first', true);
            }

            category.addEventListener('change', function () {
                rebuildRelationships(true);
            });
            rebuildRelationships(false);
        });
    </script>
@endpush
