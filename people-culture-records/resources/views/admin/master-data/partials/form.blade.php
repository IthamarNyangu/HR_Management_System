<div class="row g-3">
    @if (($config['parent'] ?? null) === 'province_id')
        <div class="col-md-6">
            <label for="province_id" class="form-label">Province</label>
            <select id="province_id" name="province_id" class="form-select" required>
                <option value="">Select province</option>
                @foreach ($provinces as $province)
                    <option value="{{ $province->id }}" @selected((string) old('province_id', $record?->province_id) === (string) $province->id)>
                        {{ $province->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    @if (($config['parent'] ?? null) === 'district_id')
        <div class="col-md-6">
            <label for="district_id" class="form-label">District</label>
            <select id="district_id" name="district_id" class="form-select" required>
                <option value="">Select district</option>
                @foreach ($districts as $district)
                    <option value="{{ $district->id }}" @selected((string) old('district_id', $record?->district_id) === (string) $district->id)>
                        {{ $district->name }} - {{ $district->province?->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="col-md-6">
        <label for="name" class="form-label">Name</label>
        <input id="name" name="name" type="text" class="form-control" value="{{ old('name', $record?->name) }}" required>
    </div>

    <div class="col-md-6">
        <label for="code" class="form-label">Code</label>
        <input id="code" name="code" type="text" class="form-control" value="{{ old('code', $record?->code) }}">
    </div>

    <div class="col-12">
        <label for="description" class="form-label">Description</label>
        <textarea id="description" name="description" rows="3" class="form-control">{{ old('description', $record?->description) }}</textarea>
    </div>

    <div class="col-12">
        <div class="form-check">
            <input id="is_active" name="is_active" type="checkbox" class="form-check-input" value="1" @checked(old('is_active', $record?->is_active ?? true))>
            <label for="is_active" class="form-check-label">Active</label>
        </div>
    </div>

    <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-md">Save Record</button>
        <a href="{{ route('admin.master-data.records', $type) }}" class="btn btn-secondary btn-md">Cancel</a>
    </div>
</div>
